# Teachers Performance Evaluation System

A web-based faculty evaluation platform built with **Laravel 13**, **Tailwind CSS**, and **Alpine.js**, with an integrated **FastAPI** machine learning microservice for performance predictions.

## Tech Stack

| Component   | Technology                        |
|-------------|-----------------------------------|
| Backend     | PHP 8.3+ / Laravel 13             |
| Frontend    | Blade + Tailwind CSS 3 + Alpine.js|
| Database    | MySQL 8.0                         |
| ML API      | Python 3.11 / FastAPI / scikit-learn |
| Build Tool  | Vite 8                            |

---

## Local Development (without Docker)

### Prerequisites

| Tool    | Version  | Install                                      |
|---------|----------|----------------------------------------------|
| PHP     | >= 8.3   | https://www.php.net/downloads                |
| Composer| >= 2.x   | https://getcomposer.org                      |
| Node.js | >= 18.x  | https://nodejs.org                           |
| MySQL   | >= 8.0   | https://dev.mysql.com/downloads              |

**Required PHP extensions:** `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `xml`

On Ubuntu/Debian:
```bash
sudo apt install php8.3 php8.3-mysql php8.3-mbstring php8.3-xml php8.3-gd php8.3-zip php8.3-bcmath php8.3-curl
```

On Windows (XAMPP/Laragon): These extensions are typically bundled. Enable them in `php.ini` if needed.

### 1. Clone the repository

```bash
git clone https://github.com/Jessie537743/teachers-performance.git
cd teachers-performance
```

### 2. Set up the database

Create a MySQL database and user:

```sql
CREATE DATABASE teachers_performance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tp_user'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON teachers_performance.* TO 'tp_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configure environment

```bash
cd src
cp .env.example .env
```

Edit `src/.env` and update the database credentials:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teachers_performance
DB_USERNAME=tp_user
DB_PASSWORD=secret
```

### 4. Install dependencies and set up

Run the automated setup script:

```bash
composer setup
```

This single command will:
- Install PHP dependencies (`composer install`)
- Generate the application key (`php artisan key:generate`)
- Run database migrations (`php artisan migrate`)
- Install Node dependencies (`npm install`)
- Build frontend assets (`npm run build`)

### 5. Seed the database (optional but recommended)

```bash
php artisan db:seed
```

This populates the database with sample data in the following order:
1. Departments
2. Default admin user
3. Role permissions
4. Faculty members and profiles
5. Students and profiles
6. Courses (all colleges)
7. Subjects
8. Subject-faculty assignments
9. Evaluation criteria and questions
10. Interventions

### 6. Start the development servers

```bash
composer dev
```

This starts all services concurrently:
- **Laravel server** at `http://localhost:8000`
- **Vite dev server** with hot reload
- **Queue worker** for background jobs
- **Log viewer** (Laravel Pail)

Alternatively, start them individually:

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite (frontend hot reload)
npm run dev
```

### 7. Access the application

Open **http://localhost:8000** in your browser.

Default admin credentials (after seeding):
| Field    | Value                |
|----------|----------------------|
| Email    | `admin@sample.com`   |
| Password | `admin123`           |

> The admin password is set from a database seed. If it doesn't work, reset it via tinker:
> ```bash
> php artisan tinker
> >>> User::where('email','admin@sample.com')->update(['password'=>bcrypt('admin123'),'must_change_password'=>false]);
> ```

---

## Useful Artisan Commands

```bash
# Run migrations
php artisan migrate

# Reset and re-seed the database
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear

# Cache config/routes for performance
php artisan optimize

# Run tests
php artisan test

# Open interactive REPL
php artisan tinker
```

---

## Docker Development (alternative)

If you prefer Docker, everything is containerized:

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)

### Start

```bash
docker compose up -d --build
```

This starts three services:

| Service      | Container    | Port          |
|--------------|-------------|---------------|
| Laravel App  | `tp-app`    | `localhost:8081` |
| MySQL        | `tp-db`     | `localhost:3307` |
| ML API       | `tp-ml-api` | `localhost:8001` |

Migrations and seeders run automatically on first start.

### Commands

```bash
# Stop containers
docker compose down

# View logs
docker compose logs -f

# Run artisan commands
docker exec tp-app php artisan <command>

# Run tests
docker exec tp-app php artisan test
```

---

## ML API

The machine learning microservice predicts faculty performance levels using a Random Forest classifier.

| Method | Endpoint              | Description                    |
|--------|-----------------------|--------------------------------|
| GET    | `/health`             | Health check                   |
| POST   | `/predict`            | Predict performance level      |
| POST   | `/train-current-term` | Train model on historical data |

### Predict example

```bash
curl -X POST http://localhost:8001/predict \
  -H "Content-Type: application/json" \
  -d '{"avg_score": 4.2, "response_count": 30, "previous_score": 3.8, "improvement_rate": 0.1}'
```

To run the ML API locally (without Docker):

```bash
cd ml_api
pip install -r requirements.txt
uvicorn app:app --host 0.0.0.0 --port 8000
```

Set `ML_API_URL=http://localhost:8000` in `src/.env` to connect the Laravel app.

---

## Project Structure

```
teachers-performance/
├── src/                        # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/   # Request handlers
│   │   ├── Models/             # Eloquent models
│   │   ├── Policies/           # Authorization policies
│   │   ├── Services/           # Business logic
│   │   └── Enums/              # Permission enum
│   ├── database/
│   │   ├── migrations/         # Database schema
│   │   └── seeders/            # Sample data
│   ├── resources/views/        # Blade templates
│   ├── routes/web.php          # Route definitions
│   └── ...
├── ml_api/                     # FastAPI ML microservice
│   ├── app.py                  # FastAPI application
│   ├── Dockerfile
│   └── requirements.txt
├── docker/                     # Docker config files
│   ├── nginx/default.conf
│   ├── supervisord.conf
│   └── entrypoint.sh
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Deployment

The application is deployed on [Railway](https://railway.app):

**Live URL:** https://teachers-performance-production.up.railway.app

Pushing to `main` triggers automatic deployment.
