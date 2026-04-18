# Teachers Performance Evaluation System

A web-based faculty evaluation system built with **Laravel 13** and a **FastAPI** machine learning microservice, fully containerized with Docker.

## Tech Stack

| Service   | Technology              | Container     |
|-----------|-------------------------|---------------|
| Backend   | PHP 8.4 / Laravel 13    | `tp-app`      |
| Web Server| Nginx (Alpine)          | `tp-nginx`    |
| Database  | MySQL 8.0               | `tp-db`       |
| ML API    | Python 3.11 / FastAPI   | `tp-ml-api`   |

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)
- Git

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd teachers-performance
```

### 2. Configure environment variables

The Laravel `.env` file is located at `src/.env`. The defaults work out of the box with the Docker setup. Key values:

| Variable        | Default                 |
|-----------------|-------------------------|
| `DB_HOST`       | `db`                    |
| `DB_DATABASE`   | `teachers_performance`  |
| `DB_USERNAME`   | `tp_user`               |
| `DB_PASSWORD`   | `secret`                |
| `ML_API_URL`    | `http://ml-api:8000`    |

### 3. Build and start the containers

```bash
docker compose up -d --build
```

This starts four services:

- **tp-app** — PHP-FPM application server
- **tp-nginx** — Nginx reverse proxy
- **tp-db** — MySQL database
- **tp-ml-api** — FastAPI ML microservice

### 4. Install dependencies and set up the database

```bash
# Install PHP dependencies (if not already installed during build)
docker exec tp-app composer install

# Run database migrations
docker exec tp-app php artisan migrate

# (Optional) Seed the database
docker exec tp-app php artisan db:seed
```

### 5. Build frontend assets

```bash
docker exec tp-app npm install
docker exec tp-app npm run build
```

## Accessing the Application

| Service          | URL                          |
|------------------|------------------------------|
| Laravel App      | http://localhost:8081         |
| ML API           | http://localhost:8001         |
| ML API Docs      | http://localhost:8001/docs    |
| MySQL            | `localhost:3307`              |

## Default Login Credentials (local dev only)

These accounts are created by `php artisan db:seed` and are intended for local development only. **Never ship or reuse these in production.** Change the admin password on first login or re-run the seeders with different values.

| Role               | Email                     | Password   |
|--------------------|---------------------------|------------|
| System Administrator | `admin@sample.com`      | `admin123` |
| Dean (CCIS)        | `dean.ccis@sample.com`    | `admin123` |

The `admin` account has the `admin` role, which bypasses every permission gate (including the new Announcements permissions) via `Gate::before` in `AppServiceProvider`. For testing tiered announcement authoring, use `dean.ccis@sample.com` to exercise the `manage-announcements-department` path.

Other seeded users (other deans, faculty, students, institution leaders) have real-world bcrypt hashes imported from production dumps and are **not** accessible with a known default password.

## ML API Endpoints

| Method | Endpoint              | Description                              |
|--------|-----------------------|------------------------------------------|
| GET    | `/`                   | Health check                             |
| GET    | `/train-current-term` | Trigger model training (placeholder)     |
| POST   | `/predict`            | Predict teacher performance              |

### Predict request example

```bash
curl -X POST http://localhost:8001/predict \
  -H "Content-Type: application/json" \
  -d '{"avg_score": 4.2, "response_count": 30, "previous_score": 3.8, "improvement_rate": 0.1}'
```

## Useful Commands

```bash
# Stop all containers
docker compose down

# View logs
docker compose logs -f

# Restart a specific service
docker compose restart app

# Run artisan commands
docker exec tp-app php artisan <command>

# Run tests
docker exec tp-app php artisan test

# Clear all caches
docker exec tp-app php artisan optimize:clear
```

## Project Structure

```
teachers-performance/
├── docker/
│   └── nginx/
│       └── default.conf        # Nginx configuration
├── ml_api/
│   ├── Dockerfile              # Python ML API image
│   ├── app.py                  # FastAPI application
│   └── requirements.txt        # Python dependencies
├── src/                        # Laravel application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── tests/
│   └── ...
├── docker-compose.yml          # Docker services definition
├── Dockerfile                  # PHP-FPM application image
└── README.md
```
