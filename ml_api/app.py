import logging
import os
from datetime import datetime, timezone
from pathlib import Path
from threading import Lock

import joblib
import numpy as np
import pandas as pd
import pymysql
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, precision_recall_fscore_support
from sklearn.model_selection import GroupShuffleSplit, train_test_split

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("ml_api")

app = FastAPI(title="Faculty Evaluation ML API")

FEATURE_NAMES = ["avg_score", "response_count", "previous_score", "improvement_rate"]
MODEL_DIR = Path("models")
MODEL_PATH_DEFAULT = MODEL_DIR / "faculty_performance_rf.joblib"
MODEL_LOCK = Lock()
# Cache keyed by (semester, school_year) so term-scoped training does not pollute the default model.
MODEL_CACHE: dict[tuple[str | None, str | None], RandomForestClassifier] = {}
MODEL_NAME = "Random Forest"
ML_API_TOKEN = os.getenv("ML_API_TOKEN")


# ---------------------------------------------------------------------------
# Auth
# ---------------------------------------------------------------------------
def require_token(x_ml_token: str | None = Header(default=None)) -> None:
    """Shared-secret guard. Disabled when ML_API_TOKEN env var is unset (dev mode)."""
    if not ML_API_TOKEN:
        return
    if x_ml_token != ML_API_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid or missing X-ML-Token header.")


# ---------------------------------------------------------------------------
# Schemas
# ---------------------------------------------------------------------------
class PredictInput(BaseModel):
    avg_score: float = Field(..., ge=1.0, le=5.0)
    response_count: int = Field(..., ge=0, le=10_000)
    previous_score: float = Field(0.0, ge=0.0, le=5.0)
    improvement_rate: float = Field(0.0, ge=-1.0, le=1.0)
    semester: str | None = None
    school_year: str | None = None


class TrainInput(BaseModel):
    semester: str | None = None
    school_year: str | None = None


# ---------------------------------------------------------------------------
# DB helpers
# ---------------------------------------------------------------------------
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


# ---------------------------------------------------------------------------
# Labeling
# ---------------------------------------------------------------------------
def _label_from_rules(
    avg_score: float, response_count: int, previous_score: float, improvement_rate: float
) -> str:
    """Institutional rubric. Used ONLY for rule/model agreement reporting and as a
    last-resort label for rows with no human-supplied performance_level."""
    response_boost = min(response_count, 100) / 100.0
    growth_boost = float(np.clip(improvement_rate, -0.40, 0.40))
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


def _normalize_performance_label(raw_label: str | None) -> str | None:
    if raw_label is None:
        return None
    label = str(raw_label).strip().lower()
    if not label:
        return None
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
    return None


# ---------------------------------------------------------------------------
# Data loading
# ---------------------------------------------------------------------------
def _load_feedback_history_rows() -> pd.DataFrame:
    query = """
        SELECT faculty_id, semester, school_year,
               CAST(total_average AS DECIMAL(10,4)) AS avg_score,
               performance_level, created_at
        FROM self_evaluation_results WHERE total_average IS NOT NULL
        UNION ALL
        SELECT faculty_id, semester, school_year,
               CAST(total_average AS DECIMAL(10,4)) AS avg_score,
               performance_level, created_at
        FROM dean_evaluation_feedback WHERE total_average IS NOT NULL
        UNION ALL
        SELECT evaluatee_faculty_id AS faculty_id, semester, school_year,
               CAST(total_average AS DECIMAL(10,4)) AS avg_score,
               performance_level, created_at
        FROM faculty_peer_evaluation_feedback
        WHERE evaluation_type = 'peer' AND total_average IS NOT NULL
    """
    return _read_query_dataframe(query)


def _apply_term_filter(df: pd.DataFrame, semester: str | None, school_year: str | None) -> pd.DataFrame:
    filtered = df
    if semester:
        filtered = filtered[filtered["semester"].astype(str).str.casefold() == semester.casefold()]
    if school_year:
        filtered = filtered[filtered["school_year"].astype(str).str.casefold() == school_year.casefold()]
    return filtered


def _prepare_training_data_from_mysql(
    semester: str | None = None, school_year: str | None = None
) -> tuple[np.ndarray, np.ndarray, np.ndarray, dict]:
    """Returns (features, labels, groups, metadata).

    CRITICAL: Only rows with a human-supplied `performance_level` are used as
    labeled training data. The rule-based formula is NOT used to fabricate
    labels — that would create a circular target where the model just memorizes
    the rule. Rule-vs-model agreement is reported separately as a sanity check.
    """
    feedback_rows = _load_feedback_history_rows()
    if feedback_rows.empty:
        raise RuntimeError("No historical feedback rows found.")

    feedback_rows["avg_score"] = (
        pd.to_numeric(feedback_rows["avg_score"], errors="coerce").clip(1.0, 5.0)
    )
    feedback_rows = feedback_rows.dropna(subset=["faculty_id", "avg_score"])
    feedback_rows["created_at"] = pd.to_datetime(feedback_rows["created_at"], errors="coerce")
    feedback_rows["normalized_label"] = feedback_rows["performance_level"].apply(
        _normalize_performance_label
    )

    grouped = (
        feedback_rows.groupby(["faculty_id", "semester", "school_year"], dropna=False)
        .agg(
            avg_score=("avg_score", "mean"),
            response_count=("avg_score", "size"),
            created_at=("created_at", "max"),
            target_label=(
                "normalized_label",
                lambda s: s.dropna().mode().iloc[0] if not s.dropna().empty else None,
            ),
        )
        .reset_index()
    )

    grouped = grouped.sort_values(["faculty_id", "created_at"], na_position="last")
    grouped["previous_score"] = grouped.groupby("faculty_id")["avg_score"].shift(1)
    grouped["previous_score"] = grouped["previous_score"].fillna(grouped["avg_score"])
    grouped["improvement_rate"] = (
        (grouped["avg_score"] - grouped["previous_score"])
        / grouped["previous_score"].replace(0, np.nan)
    ).fillna(0.0)
    grouped["improvement_rate"] = np.clip(grouped["improvement_rate"], -1.0, 1.0)

    grouped = _apply_term_filter(grouped, semester, school_year)
    labeled = grouped.dropna(subset=["target_label"]).copy()

    if labeled.empty:
        raise RuntimeError(
            "No human-labeled rows available for training. Populate `performance_level` "
            "in self/dean/peer feedback tables."
        )
    if labeled["target_label"].nunique() < 2:
        raise RuntimeError("Labeled rows currently map to only one performance class.")

    features = labeled[FEATURE_NAMES].to_numpy(dtype=float)
    labels = labeled["target_label"].to_numpy()
    groups = labeled["faculty_id"].to_numpy()
    latest = labeled.sort_values("created_at").iloc[-1]

    metadata = {
        "records_used": int(len(labeled)),
        "distinct_faculty": int(labeled["faculty_id"].nunique()),
        "latest_semester": str(latest["semester"]) if pd.notna(latest["semester"]) else None,
        "latest_school_year": str(latest["school_year"]) if pd.notna(latest["school_year"]) else None,
        "dataset_source": "human_labeled_feedback",
        "requested_semester": semester,
        "requested_school_year": school_year,
    }
    return features, labels, groups, metadata


# ---------------------------------------------------------------------------
# Persistence
# ---------------------------------------------------------------------------
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
                        MODEL_NAME, semester, school_year,
                        metrics["accuracy"], metrics["precision"], metrics["recall"], metrics["f1"],
                        metrics["training_samples"], metrics["validation_samples"],
                        metrics["model_version"], now,
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
        log.warning("Failed to persist metrics/feature importance to MySQL: %s", exc)


# ---------------------------------------------------------------------------
# Training
# ---------------------------------------------------------------------------
def _split_grouped(features, labels, groups):
    """Group-aware split: a faculty appears in only train OR only test."""
    n_groups = len(np.unique(groups))
    if n_groups < 2 or len(features) < 5:
        return train_test_split(features, labels, test_size=0.2, random_state=42)

    splitter = GroupShuffleSplit(n_splits=1, test_size=0.2, random_state=42)
    train_idx, test_idx = next(splitter.split(features, labels, groups))
    return features[train_idx], features[test_idx], labels[train_idx], labels[test_idx]


def _model_path_for(semester: str | None, school_year: str | None) -> Path:
    if not semester and not school_year:
        return MODEL_PATH_DEFAULT
    tag = f"{semester or 'all'}_{school_year or 'all'}".replace("/", "-").replace(" ", "-")
    return MODEL_DIR / f"faculty_performance_rf__{tag}.joblib"


def _train_random_forest(semester: str | None = None, school_year: str | None = None) -> dict:
    features, labels, groups, dataset_meta = _prepare_training_data_from_mysql(semester, school_year)
    x_train, x_test, y_train, y_test = _split_grouped(features, labels, groups)

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
        y_test, predictions, average="weighted", zero_division=0,
    )

    # Rule/model agreement: how often does the model agree with the institutional
    # rubric? Useful sanity check, NOT used as the training target.
    rule_labels = np.array([
        _label_from_rules(float(row[0]), int(row[1]), float(row[2]), float(row[3]))
        for row in x_test
    ])
    rule_agreement = float(np.mean(predictions == rule_labels)) if len(rule_labels) else 0.0

    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    model_path = _model_path_for(semester, school_year)
    joblib.dump({"model": model, "feature_names": FEATURE_NAMES}, model_path)

    cache_key = (semester, school_year)
    MODEL_CACHE[cache_key] = model

    importance = _feature_importance(model)
    model_metrics = {
        "accuracy": round(float(accuracy), 4),
        "precision": round(float(precision), 4),
        "recall": round(float(recall), 4),
        "f1": round(float(f1), 4),
        "rule_agreement": round(rule_agreement, 4),
        "training_samples": int(len(x_train)),
        "validation_samples": int(len(x_test)),
        "records_used": dataset_meta["records_used"],
        "distinct_faculty": dataset_meta["distinct_faculty"],
        "dataset_source": dataset_meta["dataset_source"],
        "model_version": "rf-v3-human-labeled",
    }

    _persist_training_artifacts(
        model_metrics, importance,
        dataset_meta["requested_semester"], dataset_meta["requested_school_year"],
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


def _get_or_train_model(
    semester: str | None = None, school_year: str | None = None
) -> RandomForestClassifier:
    cache_key = (semester, school_year)
    cached = MODEL_CACHE.get(cache_key)
    if cached is not None:
        return cached

    with MODEL_LOCK:
        cached = MODEL_CACHE.get(cache_key)
        if cached is not None:
            return cached

        path = _model_path_for(semester, school_year)
        if path.exists():
            stored = joblib.load(path)
            MODEL_CACHE[cache_key] = stored["model"]
            return MODEL_CACHE[cache_key]

        return _train_random_forest(semester, school_year)["model"]


def _feature_importance(model: RandomForestClassifier) -> dict[str, float]:
    importance = model.feature_importances_
    return {
        name: round(float(value), 6)
        for name, value in sorted(
            zip(FEATURE_NAMES, importance), key=lambda item: item[1], reverse=True,
        )
    }


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------
@app.get("/")
def root():
    return {"message": "ML API is running", "model": MODEL_NAME}


@app.get("/health")
def health():
    """Liveness probe — does not touch MySQL."""
    return {"status": "ok", "model_loaded": bool(MODEL_CACHE) or MODEL_PATH_DEFAULT.exists()}


@app.post("/train-current-term", dependencies=[Depends(require_token)])
def train_current_term(payload: TrainInput | None = None):
    payload = payload or TrainInput()
    with MODEL_LOCK:
        try:
            result = _train_random_forest(
                semester=payload.semester, school_year=payload.school_year
            )
        except Exception as exc:
            log.exception("Training failed")
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
        "rule_agreement": result["rule_agreement"],
        "training_samples": result["training_samples"],
        "validation_samples": result["validation_samples"],
        "records_used": result["records_used"],
        "distinct_faculty": result["distinct_faculty"],
        "latest_semester": result["latest_semester"],
        "latest_school_year": result["latest_school_year"],
        "feature_importance": result["feature_importance"],
        "model_version": result["model_version"],
    }


@app.post("/predict", dependencies=[Depends(require_token)])
def predict(data: PredictInput):
    try:
        model = _get_or_train_model(data.semester, data.school_year)
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
    rule_label = _label_from_rules(
        data.avg_score, data.response_count, data.previous_score, data.improvement_rate
    )

    return {
        "predicted_performance": label,
        "rule_label": rule_label,
        "agrees_with_rule": label == rule_label,
        "model_used": MODEL_NAME,
        "confidence": round(probability, 4),
        "feature_importance": _feature_importance(model),
    }
