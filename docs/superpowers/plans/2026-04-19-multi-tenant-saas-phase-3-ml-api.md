# Multi-Tenant SaaS — Phase 3: ML API Tenant-Awareness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the FastAPI ML microservice tenant-aware via an `X-Tenant-DB` request header so each school's predictions and trained models stay isolated. Add a cross-tenant leakage feature test that proves data isolation. Final phase — after this, multi-tenancy is feature-complete per the spec.

**Architecture:** Single FastAPI container, unchanged deployment. Requests from Laravel carry `X-Tenant-DB: <tenant_database>`. The API uses that value to (a) build a per-request MySQL connection to the right tenant DB, (b) namespace the in-process model cache by tenant, and (c) save trained model files under `models/<tenant_db>/`. The shared-secret `X-ML-Token` stays as the only auth boundary; `X-Tenant-DB` is a routing hint, not a security check (Laravel and ML API live in the same trusted network).

**Tech Stack:** Python 3.11 / FastAPI for the ML service; Laravel 13 / PHP 8.3+ for the client.

**Out of scope for Phase 3:**
- Per-tenant ML API containers (single container is fine for capstone scale).
- LRU eviction of the in-process model cache (fine for 2–5 schools).
- Re-training cadence policy (manual / on-demand only).

---

## File Structure

**New files:**

| Path | Responsibility |
|---|---|
| `src/tests/Feature/Tenancy/CrossTenantIsolationTest.php` | Verifies data isolation: tenant A's queries can't touch tenant B's DB |

**Modified files:**

| Path | Change |
|---|---|
| `ml_api/app.py` | Read DB name from `X-Tenant-DB` header per request; cache + on-disk path keyed by tenant |
| `src/app/Services/MlApiService.php` | Attach `X-Tenant-DB: <tenant->database>` header when a tenant is active |
| `docker-compose.yml` | Remove the obsolete `DB_NAME=teachers_performance` env from `ml-api` service (it now comes from the header) |

---

## Task 1: FastAPI tenant header + per-request DB connection

**Files:**
- Modify: `ml_api/app.py`

The current FastAPI service connects to a single DB whose name comes from `os.getenv("DB_NAME", "teachers_performance")`. Replace that with a per-request header read.

- [ ] **Step 1: Add a FastAPI dependency that extracts the tenant DB name**

Open `ml_api/app.py`. Near the top (after `ML_API_TOKEN = os.getenv("ML_API_TOKEN")` around line 29), add:

```python
def require_tenant_db(x_tenant_db: str | None = Header(default=None)) -> str:
    """Per-request tenant DB selector. The Laravel client sends this on every
    data-touching call. Format: `tenant_<id>` or the legacy `teachers_performance`.
    Validated to allow only [a-zA-Z0-9_] to avoid SQL identifier injection
    via _db_config()."""
    if not x_tenant_db:
        raise HTTPException(status_code=400, detail="Missing X-Tenant-DB header.")
    if not all(c.isalnum() or c == "_" for c in x_tenant_db):
        raise HTTPException(status_code=400, detail="Invalid X-Tenant-DB header.")
    return x_tenant_db
```

- [ ] **Step 2: Refactor `_db_config()` to take a tenant DB name**

Find `_db_config()` (around line 63). Change it from:

```python
def _db_config() -> dict:
    return {
        "host": os.getenv("DB_HOST", "db"),
        "port": int(os.getenv("DB_PORT", "3306")),
        "user": os.getenv("DB_USER", "tp_user"),
        "password": os.getenv("DB_PASSWORD", "secret"),
        "database": os.getenv("DB_NAME", "teachers_performance"),
        "connect_timeout": 10,
    }
```

To:

```python
def _db_config(tenant_db: str) -> dict:
    return {
        "host": os.getenv("DB_HOST", "db"),
        "port": int(os.getenv("DB_PORT", "3306")),
        "user": os.getenv("DB_USER", "tp_user"),
        "password": os.getenv("DB_PASSWORD", "secret"),
        "database": tenant_db,
        "connect_timeout": 10,
    }
```

- [ ] **Step 3: Thread `tenant_db` through `_get_connection`, `_read_query_dataframe`, and `_load_feedback_history_rows`**

Update each function signature to take `tenant_db: str` and pass it down:

```python
def _get_connection(tenant_db: str):
    return pymysql.connect(**_db_config(tenant_db), cursorclass=pymysql.cursors.DictCursor)


def _read_query_dataframe(tenant_db: str, query: str) -> pd.DataFrame:
    with _get_connection(tenant_db) as connection:
        with connection.cursor() as cursor:
            cursor.execute(query)
            rows = cursor.fetchall()
    return pd.DataFrame(rows)


def _load_feedback_history_rows(tenant_db: str) -> pd.DataFrame:
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
    return _read_query_dataframe(tenant_db, query)
```

- [ ] **Step 4: Thread `tenant_db` through training data prep + persistence**

Update `_prepare_training_data_from_mysql` to accept `tenant_db` as the first argument, and pass it to `_load_feedback_history_rows`:

```python
def _prepare_training_data_from_mysql(
    tenant_db: str,
    semester: str | None = None,
    school_year: str | None = None,
) -> tuple[np.ndarray, np.ndarray, np.ndarray, dict]:
    """Returns (features, labels, groups, metadata). ..."""
    feedback_rows = _load_feedback_history_rows(tenant_db)
    # ... rest of body unchanged ...
```

Same for `_persist_training_artifacts`:

```python
def _persist_training_artifacts(
    tenant_db: str,
    metrics: dict,
    feature_importance: dict[str, float],
    semester: str | None,
    school_year: str | None,
) -> None:
    now = datetime.now(timezone.utc).replace(tzinfo=None)
    try:
        with _get_connection(tenant_db) as connection:
            # ... rest of body unchanged ...
```

- [ ] **Step 5: Verify nothing references the old single-DB form**

```bash
grep -n "_db_config()\|_get_connection()\|_load_feedback_history_rows()\|os.getenv(.DB_NAME" ml_api/app.py
```

Expected: each result either takes a `tenant_db` argument (function calls have args) or no results at all (the `os.getenv("DB_NAME"...)` fallback should be gone).

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add ml_api/app.py
git commit -m "feat(ml-api): require X-Tenant-DB header for per-request DB routing

Replace the os.getenv('DB_NAME') fallback with a FastAPI dependency that
reads the tenant DB name from the X-Tenant-DB request header. Thread the
value through _db_config, _get_connection, _read_query_dataframe,
_load_feedback_history_rows, _prepare_training_data_from_mysql, and
_persist_training_artifacts. Header is validated [a-zA-Z0-9_] to prevent
identifier injection."
```

(Note: at this commit the route handlers haven't been updated yet — they still call the function signatures from before. The next task wires them up. The container will fail to handle requests until Task 2 lands; that's intentional.)

---

## Task 2: FastAPI tenant-namespaced cache + model files

**Files:**
- Modify: `ml_api/app.py`

- [ ] **Step 1: Re-key the in-process model cache by tenant**

Find the cache declaration:

```python
MODEL_CACHE: dict[tuple[str | None, str | None], RandomForestClassifier] = {}
```

Change to:

```python
MODEL_CACHE: dict[tuple[str, str | None, str | None], RandomForestClassifier] = {}
# key = (tenant_db, semester, school_year)
```

- [ ] **Step 2: Update `_model_path_for` to namespace by tenant**

Find it (around line 294) and change from:

```python
def _model_path_for(semester: str | None, school_year: str | None) -> Path:
    if not semester and not school_year:
        return MODEL_PATH_DEFAULT
    tag = f"{semester or 'all'}_{school_year or 'all'}".replace("/", "-").replace(" ", "-")
    return MODEL_DIR / f"faculty_performance_rf__{tag}.joblib"
```

To:

```python
def _model_path_for(tenant_db: str, semester: str | None, school_year: str | None) -> Path:
    tenant_dir = MODEL_DIR / tenant_db
    if not semester and not school_year:
        return tenant_dir / "faculty_performance_rf.joblib"
    tag = f"{semester or 'all'}_{school_year or 'all'}".replace("/", "-").replace(" ", "-")
    return tenant_dir / f"faculty_performance_rf__{tag}.joblib"
```

`MODEL_PATH_DEFAULT` is no longer used anywhere; you can leave the constant (it's harmless) or remove it.

- [ ] **Step 3: Update `_train_random_forest` to take + use tenant_db**

Change the signature and propagate:

```python
def _train_random_forest(
    tenant_db: str,
    semester: str | None = None,
    school_year: str | None = None,
) -> dict:
    features, labels, groups, dataset_meta = _prepare_training_data_from_mysql(tenant_db, semester, school_year)
    # ... rest unchanged until persistence ...

    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    model_path = _model_path_for(tenant_db, semester, school_year)
    model_path.parent.mkdir(parents=True, exist_ok=True)  # ← NEW: ensure tenant subdir exists
    joblib.dump({"model": model, "feature_names": FEATURE_NAMES}, model_path)

    cache_key = (tenant_db, semester, school_year)
    MODEL_CACHE[cache_key] = model

    # ... rest unchanged until _persist_training_artifacts call ...

    _persist_training_artifacts(
        tenant_db,
        model_metrics, importance,
        dataset_meta["requested_semester"], dataset_meta["requested_school_year"],
    )

    # ... return value unchanged ...
```

- [ ] **Step 4: Update `_get_or_train_model` to take + use tenant_db**

```python
def _get_or_train_model(
    tenant_db: str,
    semester: str | None = None,
    school_year: str | None = None,
) -> RandomForestClassifier:
    cache_key = (tenant_db, semester, school_year)
    cached = MODEL_CACHE.get(cache_key)
    if cached is not None:
        return cached

    with MODEL_LOCK:
        cached = MODEL_CACHE.get(cache_key)
        if cached is not None:
            return cached

        path = _model_path_for(tenant_db, semester, school_year)
        if path.exists():
            stored = joblib.load(path)
            MODEL_CACHE[cache_key] = stored["model"]
            return MODEL_CACHE[cache_key]

        return _train_random_forest(tenant_db, semester, school_year)["model"]
```

- [ ] **Step 5: Update the route handlers to depend on `require_tenant_db`**

Update `/health` (does NOT need the tenant — leave alone; it doesn't query DB):

```python
@app.get("/health")
def health():
    return {"status": "ok", "model_loaded": bool(MODEL_CACHE)}
```

Update `/train-current-term`:

```python
@app.post("/train-current-term", dependencies=[Depends(require_token)])
def train_current_term(
    payload: TrainInput | None = None,
    tenant_db: str = Depends(require_tenant_db),
):
    payload = payload or TrainInput()
    with MODEL_LOCK:
        try:
            result = _train_random_forest(
                tenant_db,
                semester=payload.semester,
                school_year=payload.school_year,
            )
        except Exception as exc:
            log.exception("Training failed for tenant %s", tenant_db)
            raise HTTPException(status_code=400, detail=f"Training failed: {exc}") from exc

    return {
        "status": "trained",
        "tenant_db": tenant_db,
        # ... rest of the response body unchanged from the existing implementation ...
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
```

Update `/predict`:

```python
@app.post("/predict", dependencies=[Depends(require_token)])
def predict(
    data: PredictInput,
    tenant_db: str = Depends(require_tenant_db),
):
    try:
        model = _get_or_train_model(tenant_db, data.semester, data.school_year)
    except Exception as exc:
        raise HTTPException(
            status_code=503,
            detail=f"Model unavailable for tenant {tenant_db}. Run /train-current-term first. Reason: {exc}",
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
        "tenant_db": tenant_db,
        "predicted_performance": label,
        "rule_label": rule_label,
        "agrees_with_rule": label == rule_label,
        "model_used": MODEL_NAME,
        "confidence": round(probability, 4),
        "feature_importance": _feature_importance(model),
    }
```

- [ ] **Step 6: Restart the ml-api container so the new app.py is loaded**

```bash
docker compose restart ml-api
```

If the container doesn't reload Python automatically, force-recreate:

```bash
docker compose up -d --force-recreate ml-api
```

- [ ] **Step 7: Smoke test the new contract**

```bash
# Without the header — should 400
curl -sS -o /dev/null -w "no header → HTTP %{http_code}\n" -X POST -H "Content-Type: application/json" -d '{"avg_score":4.2,"response_count":30}' http://localhost:8001/predict

# With a known-good tenant DB
curl -sS -X POST -H "Content-Type: application/json" -H "X-Tenant-DB: teachers_performance" -d '{"avg_score":4.2,"response_count":30,"previous_score":3.8,"improvement_rate":0.1}' http://localhost:8001/predict | head -c 300
```

Expected: first curl returns 400, second curl returns a JSON prediction (or a 503 if no trained model exists yet for JCD — that's expected, it just means the JCD tenant has no trained model on disk).

If the second curl returns `Detail: Missing X-Tenant-DB header`, the dependency wiring didn't take. Re-check Task 2 step 5.

- [ ] **Step 8: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add ml_api/app.py
git commit -m "feat(ml-api): namespace model cache and on-disk files by tenant

Re-key MODEL_CACHE as (tenant_db, semester, school_year) and prefix
on-disk model paths with the tenant directory so two schools never share
or overwrite each other's trained models. Wire X-Tenant-DB through the
two data-touching route handlers (/train-current-term and /predict);
/health stays tenant-agnostic for liveness probes."
```

---

## Task 3: Laravel client — attach `X-Tenant-DB` header

**Files:**
- Modify: `src/app/Services/MlApiService.php`

The Laravel client needs to add the header on every outbound request to the ML API. The active tenant is available via `tenant()` (provided by stancl when tenancy is initialized).

- [ ] **Step 1: Update the `client()` method to attach the header**

Open `src/app/Services/MlApiService.php`. Find the `client()` method (around line 24) and update:

```php
private function client(int $timeout = 10): PendingRequest
{
    $client = Http::timeout($timeout)->acceptJson();

    if (filled($this->token)) {
        $client = $client->withHeaders(['X-ML-Token' => $this->token]);
    }

    // Attach the active tenant DB so the ML API queries the right school.
    // tenant() is null in central context (super-admin operations); the
    // ML API will return 400 in that case, which is the correct behavior —
    // ML calls only make sense from inside a tenant request.
    if (function_exists('tenant') && tenant() !== null) {
        $client = $client->withHeaders([
            'X-Tenant-DB' => tenant()->getAttribute('database'),
        ]);
    }

    return $client;
}
```

- [ ] **Step 2: Verify by tinker (initialize tenancy, check the header would be sent)**

```bash
docker exec tp-app php artisan tinker --execute="
use App\Models\Tenant;
\$t = Tenant::find(1);
tenancy()->initialize(\$t);
echo 'Active tenant DB: ' . tenant()->getAttribute('database') . PHP_EOL;
echo 'Sample header value would be: X-Tenant-DB: ' . tenant()->getAttribute('database') . PHP_EOL;
tenancy()->end();
"
```

Expected: `X-Tenant-DB: teachers_performance`.

- [ ] **Step 3: Smoke test the client end-to-end**

```bash
docker exec tp-app php artisan tinker --execute="
use App\Models\Tenant;
\$t = Tenant::find(1);
tenancy()->initialize(\$t);
\$svc = new App\Services\MlApiService();
\$result = \$svc->predict(4.2, 30, 3.8, 0.1);
echo json_encode(\$result, JSON_PRETTY_PRINT) . PHP_EOL;
tenancy()->end();
" 2>&1 | tail -15
```

Expected: a JSON response from the ML API. Either:
- A successful prediction with a `tenant_db` field set to `teachers_performance`, OR
- An error like `Model unavailable for tenant teachers_performance. Run /train-current-term first.` — also acceptable; it proves the header was forwarded correctly.

If you get `Missing X-Tenant-DB header`, the header attach didn't take. Re-check Task 3 step 1.

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Services/MlApiService.php
git commit -m "feat(ml-api): Laravel client sends X-Tenant-DB header

The MlApiService now attaches the active tenant's database name to every
outbound request so the FastAPI service queries the right school's data
and reads/writes the right tenant-namespaced model files."
```

---

## Task 4: Drop obsolete `DB_NAME` env from ml-api docker service

**Files:**
- Modify: `docker-compose.yml`

The `ml-api` service currently inherits a default DB name from env (or hardcoded fallback). Now that the header is the sole source of truth, the env var is misleading and should go.

- [ ] **Step 1: Inspect the `ml-api` service env block**

Open `docker-compose.yml`. The `ml-api` service has minimal env. If it has any of `DB_NAME`, `DB_HOST`, `DB_USER`, `DB_PASSWORD`, those should be reviewed:

- `DB_HOST=db`, `DB_USER=tp_user`, `DB_PASSWORD=secret` — KEEP. The ML API still needs these to know how to connect to the MySQL server. Only the database SELECTION moves to the header.
- `DB_NAME=teachers_performance` — REMOVE. It's now the per-request header.

If none of these env vars are set on the `ml-api` service explicitly, the ML API was relying on its hardcoded `os.getenv` fallbacks. Those fallbacks are still in `_db_config()` for everything except `database` (which now requires the header). No action needed beyond the code change.

- [ ] **Step 2: Make the change if needed**

If you find a `DB_NAME` line in the `ml-api` env block, remove it. Otherwise, this task is a no-op.

- [ ] **Step 3: Restart and verify**

```bash
docker compose restart ml-api
curl -sS -X POST -H "Content-Type: application/json" -H "X-Tenant-DB: teachers_performance" -d '{"avg_score":4.2,"response_count":30}' http://localhost:8001/predict 2>&1 | head -c 200
```

Expected: 200 response or 503 (no trained model). NOT a connection error.

- [ ] **Step 4: Commit (if anything changed)**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add docker-compose.yml
git commit -m "chore(ml-api): drop obsolete DB_NAME env — now per-request header"
```

If nothing changed, skip the commit and report the task as DONE.

---

## Task 5: Cross-tenant isolation feature test

**Files:**
- Create: `src/tests/Feature/Tenancy/CrossTenantIsolationTest.php`

A test that creates two tenants, asserts that data written inside tenant A is invisible from tenant B's request context. This is the headline guarantee of the multi-tenancy work — if it ever regresses, this test catches it.

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrossTenantIsolationTest extends TestCase
{
    public function test_data_written_in_tenant_a_is_invisible_from_tenant_b(): void
    {
        // Use the existing JCD tenant as A.
        $tenantA = Tenant::where('subdomain', 'jcd')->firstOrFail();

        // Provision a temporary tenant B for this test. We don't run the full
        // ProvisionTenantJob (slow, creates real DB+migrations); instead we
        // create a Tenant row pointing at a hand-created small DB that has
        // just the `users` table — enough to prove isolation.
        $tenantBdb = 'isolation_test_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement(
            "CREATE DATABASE `{$tenantBdb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        try {
            $tenantB = Tenant::create([
                'name'      => 'Isolation Test School',
                'subdomain' => str_replace('_', '-', $tenantBdb),
                'database'  => $tenantBdb,
                'status'    => 'active',
            ]);
            $tenantB->domains()->create(['domain' => str_replace('_', '-', $tenantBdb)]);

            // Run the users migration against tenant B so we have a table to compare.
            \Artisan::call('tenants:migrate', [
                '--tenants' => [(string) $tenantB->id],
                '--force'   => true,
            ]);

            // Sanity check: tenant A has many users, tenant B has none.
            tenancy()->initialize($tenantA);
            $countA = DB::table('users')->count();
            tenancy()->end();

            tenancy()->initialize($tenantB);
            $countB = DB::table('users')->count();
            tenancy()->end();

            $this->assertGreaterThan(0, $countA, 'JCD should have users (Phase 1 baseline).');
            $this->assertSame(0, $countB, 'Fresh tenant B should have zero users.');

            // The actual isolation assertion: insert a unique marker in B's users,
            // then switch to A and verify it's NOT visible.
            tenancy()->initialize($tenantB);
            $markerEmail = 'leakage-marker-' . uniqid() . '@isolation.test';
            DB::table('users')->insert([
                'name'                 => 'Marker User',
                'email'                => $markerEmail,
                'password'             => bcrypt('x'),
                'is_active'            => true,
                'must_change_password' => false,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            $countBafter = DB::table('users')->where('email', $markerEmail)->count();
            tenancy()->end();

            $this->assertSame(1, $countBafter, 'Marker should be visible in tenant B.');

            tenancy()->initialize($tenantA);
            $countAhasMarker = DB::table('users')->where('email', $markerEmail)->count();
            tenancy()->end();

            $this->assertSame(
                0,
                $countAhasMarker,
                "Tenant A leaked: tenant B's marker user is visible from tenant A's context.",
            );
        } finally {
            // Cleanup: drop the temp DB and the central rows.
            try {
                if (isset($tenantB)) {
                    $tenantB->domains()->delete();
                    $tenantB->delete();
                }
                DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$tenantBdb}`");
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
    }
}
```

- [ ] **Step 2: Run the test**

```bash
docker exec tp-app php artisan test --filter=CrossTenantIsolationTest 2>&1 | tail -15
```

Expected: 1 test passes. The test takes ~10s because it runs migrations against a fresh DB.

If the assertion `Tenant A leaked: ...` fails, that means the connection swap is not actually swapping — the tenant context isn't isolating. Re-check the Phase 2 fix in commit `6765b28` (TenancyServiceProvider registration).

- [ ] **Step 3: Run the full Tenancy test suite to confirm no regression**

```bash
docker exec tp-app php artisan test --filter=Tenancy 2>&1 | tail -15
```

Expected: 7 tests pass (6 from Phase 1's `TenantSubdomainResolutionTest` + 1 new isolation test).

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/tests/Feature/Tenancy/CrossTenantIsolationTest.php
git commit -m "test(tenancy): cross-tenant data isolation guarantee

Provisions a temporary second tenant, writes a marker row inside it,
then asserts the marker is invisible from the first tenant's context.
This is the headline guarantee of the multi-tenancy migration — if
isolation ever regresses, this test catches it before the data does."
```

---

## Task 6: Final smoke test — end-to-end multi-tenant ML prediction

Manual + curl verification that the whole stack works for a fresh tenant.

- [ ] **Step 1: Provision a fresh "Demo" school via the super-admin UI**

Browser: log into `http://admin.localhost:8081/login` (`super@platform.test` / `super123`). Create a new school named "Demo", subdomain `demo`, admin email `demo-admin@demo.test`. Note the temp password.

(If you already have a Demo school from the Phase 2 smoke test, suspend + delete it manually first to start fresh, OR pick a new subdomain like `demo2`.)

- [ ] **Step 2: Verify the ML API rejects requests without the header**

```bash
curl -sS -o /dev/null -w "no header → HTTP %{http_code}\n" -X POST \
    -H "Content-Type: application/json" \
    -d '{"avg_score":4.2,"response_count":30}' \
    http://localhost:8001/predict
```

Expected: `400`.

- [ ] **Step 3: Verify the ML API works for the new tenant**

```bash
curl -sS -X POST \
    -H "Content-Type: application/json" \
    -H "X-Tenant-DB: tenant_<demo-id>" \
    -d '{"avg_score":4.2,"response_count":30,"previous_score":3.8,"improvement_rate":0.1}' \
    http://localhost:8001/predict | head -c 300
```

(Replace `<demo-id>` with the actual id, visible in the super-admin tenants page.)

Expected: a 503 with a message like "Model unavailable for tenant tenant_X. Run /train-current-term first." — this is correct because the new tenant has no training data and no model. The point is the API correctly TRIED to query the right tenant DB.

- [ ] **Step 4: Verify the model file path namespacing**

If you've successfully trained a model for any tenant, check the directory structure:

```bash
docker exec tp-ml-api ls -la /app/models 2>&1 | tail -10
```

Expected: subdirectories named after each tenant DB (e.g., `teachers_performance/`, `tenant_2/`). Files inside follow the `faculty_performance_rf__<term>.joblib` pattern.

- [ ] **Step 5: Verify JCD's ML predictions still work**

Browser: log in to `http://jcd.localhost:8081` as `admin@sample.com` / `admin123`. Navigate to a page that triggers an ML prediction (e.g., an evaluation summary or analytics view). Confirm it doesn't 500.

(If JCD has historical evaluation data — which it does post-restore — the `/predict` should succeed once a model is trained.)

- [ ] **Step 6: No commit — manual verification step**

---

## Phase 3 Done — Verification Checklist

- [ ] FastAPI requires `X-Tenant-DB` on `/predict` and `/train-current-term`. Returns 400 if missing.
- [ ] FastAPI uses the header value as the MySQL DB name for that request.
- [ ] FastAPI's `MODEL_CACHE` is keyed by `(tenant_db, semester, school_year)` — two tenants never share a cached model.
- [ ] Trained model files saved under `models/<tenant_db>/...` — two tenants never overwrite each other's `.joblib`.
- [ ] Laravel `MlApiService` attaches `X-Tenant-DB: <tenant->database>` on every request when tenancy is initialized.
- [ ] `docker-compose.yml` no longer exports `DB_NAME` to the `ml-api` service (or never did — verified).
- [ ] `CrossTenantIsolationTest` passes — proves a write inside tenant B is invisible from tenant A.
- [ ] All Phase 1, 2, 3 feature tests pass: `php artisan test --filter='Tenancy|SuperAdmin' 2>&1 | tail`.
- [ ] JCD continues to function at `jcd.localhost:8081`.
- [ ] Demo (or other newly-provisioned tenant) functions at `<sub>.localhost:8081`.

When all boxes are ticked, the multi-tenant SaaS migration (Phases 1–3) is complete per the spec.
