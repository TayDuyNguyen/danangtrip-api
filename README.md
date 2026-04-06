# DA NANG TRIP - Graduation Project

## 1. Getting Started with Docker

This project is containerized using Docker and connects to a Supabase (PostgreSQL) database.

### Prerequisites

- Docker & Docker Compose installed on your system.
- A `.env` file configured with your Supabase credentials.

### Essential Docker Commands

> **Note**: All commands should be run from the project root directory.

**1. Managing Containers:**

```bash
# Start the project in the background
docker compose -f docker/docker-compose.yml up -d

# Stop and remove containers
docker compose -f docker/docker-compose.yml down

# Restart the app container
docker compose -f docker/docker-compose.yml restart app
```

**2. Database Management (Crucial for Development):**

```bash
# Run pending migrations
docker exec -it danangtrip_app php artisan migrate

# RESET DATABASE (Wipe all data and re-run all migrations)
docker exec -it danangtrip_app php artisan migrate:fresh

# RESET DATABASE + SEED (Wipe all data and populate with dummy records)
docker exec -it danangtrip_app php artisan migrate:fresh --seed

# Check migration history and status
docker exec -it danangtrip_app php artisan migrate:status
```

**3. Useful Development Tools:**

```bash
# Clear all application cache (Use if changes aren't reflecting)
docker exec -it danangtrip_app php artisan optimize:clear

# Clear config cache (Use after modifying .env)
docker exec -it danangtrip_app php artisan config:clear

# Display all registered API routes
docker exec -it danangtrip_app php artisan route:list

# Generate a new Model, Migration, and Controller (Scaffold)
docker exec -it danangtrip_app php artisan make:model YourModel -mc

# Access the application container's terminal
docker exec -it danangtrip_app bash
```

**4. Environment & Rebuilding:**

```bash
# Force a rebuild of images (Necessary after modifying Dockerfile or Nginx config)
docker compose -f docker/docker-compose.yml up -d --build
```

### 📦 Package Management (Composer/NPM)

The project uses **Docker Volumes** (`- ../:/var/www`), meaning your local source code and the code inside the container are automatically synchronized.

- **Installing via Docker (Recommended):** Use `docker exec -it danangtrip_app composer require <package-name>`. This ensures 100% compatibility with the project's environment.
- **Updating Packages:**
    ```bash
    docker exec -it danangtrip_app composer update
    ```
- **Troubleshooting:** If you experience errors after pulling code from Git or installing packages locally, run:
    ```bash
    docker exec -it danangtrip_app composer install
    ```

## 📖 API Documentation

To generate and view the API documentation:

```bash
# Generate documentation
npm run apidoc

# Access via browser
# http://localhost:8000/apidoc/
```

## 🛠️ Code Quality & Testing

To ensure the code follows best practices and passes all tests (Formatting, PHPStan, PHPUnit):

```bash
# Run all checks (Lint, Static Analysis, Tests)
composer check

# Or individually
composer format
composer analyze
composer test
```

## Project Overview

**Topic**: BUILDING "DA NANG TRIP" TRAVEL WEBSITE - INTEGRATING A RATING AND CONTENT MANAGEMENT SYSTEM POWERED BY REWARD POINTS.

- **Backend Framework**: Laravel 12
- **Database**: Supabase (PostgreSQL)
- **Environment**: Docker
