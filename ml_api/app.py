import os
from datetime import datetime, timezone
from pathlib import Path
from threading import Lock

import joblib
import numpy as np
import pandas as pd
import pymysql
from fastapi import FastAPI, HTTPException, Query
from pydantic import BaseModel
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, precision_recall_fscore_support
from sklearn.model_selection import train_test_split

app = FastAPI(title="Faculty Evaluation ML API")

FEATURE_NAMES = ["avg_score", "response_count", "previous_score", "improvement_rate"]
MODEL_DIR = Path("models")
MODEL_PATH = MODEL_DIR / "faculty_performance_rf.joblib"
MODEL_LOCK = Lock()
MODEL_CACHE = None
MODEL_NAME = "Random Forest"


class PredictInput(BaseModel):
    avg_score: float
    response_count: int
    previous_score: float = 0.0
    improvement_rate: float = 0.0


def _db_config() -> dict:
    return {
        "host": os.getenv("DB_HOST", "db"),
        "port": int(os.getenv("DB_PORT", "3306")),
        "user": os.getenv("DB_USER", "tp_user"),
        "password": os.getenv("DB_PASSWORD", "secret"),
        "database": os.getenv("DB_NAME", "teachers_performance"),
        "connect_timeout": 10,
    }


def _get_connection():
    return pymysql.connect(**_db_config(), cursorclass=pymysql.cursors.DictCursor)


def _read_query_dataframe(query: str) -> pd.DataFrame:
    with _get_connection() as connection:
        with connection.cursor() as cursor:
            cursor.execute(query)
            rows = cursor.fetchall()
    return pd.DataFrame(rows)


def _label_from_rules(
    avg_score: float, response_count: int, previous_score: float, improvement_rate: float
) -> str:
    response_boost = min(response_count, 100) / 100.0
    growth_boost = np.clip(improvement_rate, -0.40, 0.40)
    combined = (
        (avg_score * 0.72)
        + (previous_score * 0.20)
        + (growth_boost * 1.10)
        + (response_boost * 0.18)
    )

    if combined >= 4.40:
        return "Excellent"
    if combined >= 3.55:
        return "Very Good"
    if combined >= 2.70:
        return "Good"
    if combined >= 2.10:
        return "Needs Improvement"
    return "At Risk"


def _load_feedback_history_rows() -> pd.DataFrame:
    query = """
        SELECT
            faculty_id,
            semester,
            school_year,
            CAST(total_average AS DECIMAL(10,4)) AS avg_score,
            performance_level,
            created_at
        FROM self_evaluation_results
        WHERE total_average IS NOT NULL

        UNION ALL

        SELECT
            faculty_id,
            semester,
            school_year,
            CAST(total_average AS DECIMAL(10,4)) AS avg_score,
            performance_level,
            created_at
        FROM dean_evaluation_feedback
        WHERE total_average IS NOT NULL

        UNION ALL

        SELECT
            evaluatee_faculty_id AS faculty_id,
            semester,
            school_year,
            CAST(total_average AS DECIMAL(10,4)) AS avg_score,
            performance_level,
            created_at
        FROM faculty_peer_evaluation_feedback
        WHERE evaluation_type = 'peer' AND total_average IS NOT NULL
    """
    return _read_query_dataframe(query)


def _load_summary_history_rows() -> pd.DataFrame:
    query = """
        SELECT
            faculty_id,
            semester,
            school_year,
            CAST(avg_score AS DECIMAL(10,4)) AS avg_score,
            COALESCE(total_responses, 0) AS response_count,
            COALESCE(previous_score, avg_score) AS previous_score,
            COALESCE(improvement_rate, 0) AS improvement_rate,
            evaluation_date
        FROM faculty_evaluation_summary
        WHERE avg_score IS NOT NULL
        ORDER BY faculty_id, school_year, semester, evaluation_date
    """
    return _read_query_dataframe(query)


def _normalize_performance_label(raw_label: str | None, avg_score: float) -> str:
    if raw_label is None:
        return _label_from_rules(avg_score, 0, avg_score, 0.0)

    label = str(raw_label).strip().lower()
    if any(word in label for word in ["excellent", "outstanding"]):
        return "Excellent"
    if any(word in label for word in ["very good", "very satisfactory"]):
        return "Very Good"
    if any(word in label for word in ["good", "satisfactory"]):
        return "Good"
    if any(word in label for word in ["needs improvement", "fair"]):
        return "Needs Improvement"
    if any(word in label for word in ["at risk", "poor", "unsatisfactory"]):
        return "At Risk"

    return _label_from_rules(avg_score, 0, avg_score, 0.0)


def _apply_term_filter(
    df: pd.DataFrame, semester: str | None, school_year: str | None
) -> pd.DataFrame:
    filtered = df
    if semester:
        filtered = filtered[filtered["semester"].astype(str).str.casefold() == semester.casefold()]
    if school_year:
        filtered = filtered[filtered["school_year"].astype(str).str.casefold() == school_year.casefold()]
    return filtered


def _prepare_training_data_from_mysql(
    semester: str | None = None, school_year: str | None = None
) -> tuple[np.ndarray, np.ndarray, dict]:
    feedback_rows = _load_feedback_history_rows()
    if not feedback_rows.empty:
        feedback_rows["avg_score"] = pd.to_numeric(feedback_rows["avg_score"], errors="coerce").clip(1.0, 5.0)
        feedback_rows = feedback_rows.dropna(subset=["faculty_id", "avg_score"])
        feedback_rows["created_at"] = pd.to_datetime(feedback_rows["created_at"], errors="coerce")

        grouped = (
            feedback_rows.groupby(["faculty_id", "semester", "school_year"], dropna=False)
            .agg(
                avg_score=("avg_score", "mean"),
                response_count=("avg_score", "size"),
                created_at=("created_at", "max"),
                raw_label=(
                    "performance_level",
                    lambda s: s.dropna().mode().iloc[0] if not s.dropna().empty else None,
                ),
            )
            .reset_index()
        )

        grouped = grouped.sort_values(["faculty_id", "created_at"], na_position="last")
        grouped["previous_score"] = grouped.groupby("faculty_id")["avg_score"].shift(1)
        grouped["previous_score"] = grouped["previous_score"].fillna(grouped["avg_score"])
        grouped["improvement_rate"] = (
            (grouped["avg_score"] - grouped["previous_score"]) / grouped["previous_score"].replace(0, np.nan)
        ).fillna(0.0)
        grouped["improvement_rate"] = np.clip(grouped["improvement_rate"], -1.0, 1.0)
        grouped["target_label"] = grouped.apply(
            lambda row: _normalize_performance_label(row["raw_label"], float(row["avg_score"])),
            axis=1,
        )
        grouped["dataset_source"] = "historical_feedback"
        df = _apply_term_filter(grouped, semester, school_year)
    else:
        df = _load_summary_history_rows()
        if df.empty:
            raise RuntimeError(
                "No historical records found. Populate self/dean/peer feedback or faculty_evaluation_summary first."
            )

        df["avg_score"] = pd.to_numeric(df["avg_score"], errors="coerce").clip(1.0, 5.0)
        df["response_count"] = pd.to_numeric(df["response_count"], errors="coerce").fillna(0).clip(0, 500)
        df["previous_score"] = (
            pd.to_numeric(df["previous_score"], errors="coerce")
            .fillna(df["avg_score"])
            .clip(1.0, 5.0)
        )
        raw_improvement = pd.to_numeric(df["improvement_rate"], errors="coerce").fillna(0.0)
        # Stored value can be ratio (-1..1) or percent (-100..100); normalize to ratio.
        df["improvement_rate"] = np.where(raw_improvement.abs() > 1.0, raw_improvement / 100.0, raw_improvement)
        df["improvement_rate"] = np.clip(df["improvement_rate"], -1.0, 1.0)
        df = df.dropna(subset=["avg_score", "previous_score"])
        df["target_label"] = df.apply(
            lambda row: _label_from_rules(
                float(row["avg_score"]),
                int(row["response_count"]),
                float(row["previous_score"]),
                float(row["improvement_rate"]),
            ),
            axis=1,
        )
        df["dataset_source"] = "faculty_evaluation_summary"
        df = _apply_term_filter(df, semester, school_year)

    if df.empty:
        filter_message = []
        if semester:
            filter_message.append(f"semester='{semester}'")
        if school_year:
            filter_message.append(f"school_year='{school_year}'")
        suffix = f" for {', '.join(filter_message)}" if filter_message else ""
        raise RuntimeError(f"Historical records are incomplete after cleaning{suffix}.")

    label_counts = df["target_label"].value_counts()
    if label_counts.shape[0] < 2:
        raise RuntimeError("Historical records currently map to only one performance class.")

    features = df[FEATURE_NAMES].to_numpy(dtype=float)
    labels = df["target_label"].to_numpy()
    sort_column = "evaluation_date" if "evaluation_date" in df.columns else "created_at"
    latest = df.sort_values(sort_column).iloc[-1]

    metadata = {
        "records_used": int(len(df)),
        "distinct_faculty": int(df["faculty_id"].nunique()),
        "latest_semester": str(latest["semester"]) if pd.notna(latest["semester"]) else None,
        "latest_school_year": str(latest["school_year"]) if pd.notna(latest["school_year"]) else None,
        "dataset_source": str(latest["dataset_source"]),
        "requested_semester": semester,
        "requested_school_year": school_year,
    }
    return features, labels, metadata


def _persist_training_artifacts(
    metrics: dict,
    feature_importance: dict[str, float],
    semester: str | None,
    school_year: str | None,
) -> None:
    now = datetime.now(timezone.utc).replace(tzinfo=None)
    try:
        with _get_connection() as connection:
            with connection.cursor() as cursor:
                cursor.execute(
                    """
                    INSERT INTO ai_model_metrics
                    (model_name, semester, school_year, accuracy, precision_score, recall_score, f1_score,
                     training_samples, testing_samples, model_version, training_date)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        MODEL_NAME,
                        semester,
                        school_year,
                        metrics["accuracy"],
                        metrics["precision"],
                        metrics["recall"],
                        metrics["f1"],
                        metrics["training_samples"],
                        metrics["validation_samples"],
                        metrics["model_version"],
                        now,
                    ),
                )

                for feature_name, score in feature_importance.items():
                    cursor.execute(
                        """
                        INSERT INTO ai_feature_importance
                        (model_name, feature_name, importance_score, semester, school_year, recorded_date)
                        VALUES (%s, %s, %s, %s, %s, %s)
                        """,
                        (MODEL_NAME, feature_name, score, semester, school_year, now),
                    )
            connection.commit()
    except Exception as exc:
        print(f"Warning: failed to persist metrics/feature importance to MySQL: {exc}")


def _train_random_forest(semester: str | None = None, school_year: str | None = None) -> dict:
    features, labels, dataset_meta = _prepare_training_data_from_mysql(semester, school_year)
    unique, counts = np.unique(labels, return_counts=True)
    can_stratify = bool(np.all(counts >= 2))
    x_train, x_test, y_train, y_test = train_test_split(
        features,
        labels,
        test_size=0.2,
        random_state=42,
        stratify=labels if can_stratify else None,
    )

    model = RandomForestClassifier(
        n_estimators=300,
        max_depth=12,
        min_samples_leaf=2,
        random_state=42,
        class_weight="balanced_subsample",
    )
    model.fit(x_train, y_train)
    predictions = model.predict(x_test)
    accuracy = accuracy_score(y_test, predictions)
    precision, recall, f1, _ = precision_recall_fscore_support(
        y_test,
        predictions,
        average="weighted",
        zero_division=0,
    )

    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    joblib.dump({"model": model, "feature_names": FEATURE_NAMES}, MODEL_PATH)

    global MODEL_CACHE
    MODEL_CACHE = model

    model_metrics = {
        "accuracy": round(float(accuracy), 4),
        "precision": round(float(precision), 4),
        "recall": round(float(recall), 4),
        "f1": round(float(f1), 4),
        "training_samples": int(len(x_train)),
        "validation_samples": int(len(x_test)),
        "records_used": dataset_meta["records_used"],
        "distinct_faculty": dataset_meta["distinct_faculty"],
        "dataset_source": dataset_meta["dataset_source"],
        "model_version": "rf-v2-mysql-history",
    }

    importance = _feature_importance(model)
    _persist_training_artifacts(
        model_metrics,
        importance,
        dataset_meta["requested_semester"],
        dataset_meta["requested_school_year"],
    )

    return {
        "model": model,
        **model_metrics,
        "feature_importance": importance,
        "latest_semester": dataset_meta["latest_semester"],
        "latest_school_year": dataset_meta["latest_school_year"],
        "requested_semester": dataset_meta["requested_semester"],
        "requested_school_year": dataset_meta["requested_school_year"],
    }


def _get_or_train_model() -> RandomForestClassifier:
    global MODEL_CACHE
    if MODEL_CACHE is not None:
        return MODEL_CACHE

    with MODEL_LOCK:
        if MODEL_CACHE is not None:
            return MODEL_CACHE

        if MODEL_PATH.exists():
            stored = joblib.load(MODEL_PATH)
            MODEL_CACHE = stored["model"]
            return MODEL_CACHE

        return _train_random_forest()["model"]


def _feature_importance(model: RandomForestClassifier) -> dict[str, float]:
    importance = model.feature_importances_
    return {
        name: round(float(value), 6)
        for name, value in sorted(
            zip(FEATURE_NAMES, importance),
            key=lambda item: item[1],
            reverse=True,
        )
    }


@app.get("/")
def root():
    return {"message": "ML API is running", "model": MODEL_NAME}


@app.get("/train-current-term")
def train_current_term(
    semester: str | None = Query(default=None, description="Optional term filter, e.g. 1st or 2nd"),
    school_year: str | None = Query(default=None, description="Optional school year filter, e.g. 2023-2024"),
):
    with MODEL_LOCK:
        try:
            result = _train_random_forest(semester=semester, school_year=school_year)
        except Exception as exc:
            raise HTTPException(status_code=400, detail=f"Training failed: {exc}") from exc

    return {
        "status": "trained",
        "model_used": MODEL_NAME,
        "data_source": f"MySQL {result['dataset_source']}",
        "requested_semester": result["requested_semester"],
        "requested_school_year": result["requested_school_year"],
        "accuracy": result["accuracy"],
        "precision": result["precision"],
        "recall": result["recall"],
        "f1_score": result["f1"],
        "training_samples": result["training_samples"],
        "validation_samples": result["validation_samples"],
        "records_used": result["records_used"],
        "distinct_faculty": result["distinct_faculty"],
        "latest_semester": result["latest_semester"],
        "latest_school_year": result["latest_school_year"],
        "feature_importance": result["feature_importance"],
    }


@app.post("/predict")
def predict(data: PredictInput):
    try:
        model = _get_or_train_model()
    except Exception as exc:
        raise HTTPException(
            status_code=503,
            detail=f"Model unavailable. Run /train-current-term first. Reason: {exc}",
        ) from exc
    row = np.array(
        [[data.avg_score, data.response_count, data.previous_score, data.improvement_rate]],
        dtype=float,
    )

    label = str(model.predict(row)[0])
    probability = float(np.max(model.predict_proba(row)[0]))

    return {
        "predicted_performance": label,
        "model_used": MODEL_NAME,
        "confidence": round(probability, 4),
        "feature_importance": _feature_importance(model),
    }
