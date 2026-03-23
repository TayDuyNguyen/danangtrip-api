# DA NANG TRIP - Graduation Project

## 1. Getting Started with Docker

This project is containerized using Docker and connects to a Supabase (PostgreSQL) database.

### Prerequisites

- Docker & Docker Compose installed on your system.
- A `.env` file configured with your Supabase credentials.

### Essential Docker Commands

**1. Managing Containers:**

```bash
# Start the project in the background
docker-compose up -d

# Stop and remove containers
docker-compose down
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

# Display all registered API routes
docker exec -it danangtrip_app php artisan route:list

# Generate a new Model, Migration, and Controller (Scaffold)
docker exec -it danangtrip_app php artisan make:model YourModel -mc

# Access the application container's terminal
docker exec -it danangtrip_app bash
```

**4. Rebuilding the Environment:**

```bash
# Force a rebuild of images (Necessary after modifying Dockerfile or Nginx config)
docker-compose up -d --build
```

### 📦 Docker & Package Management (Composer/NPM)

The project uses **Docker Volumes** (`- ../:/var/www`), meaning your local source code and the code inside the container are automatically synchronized.

- **Installing packages locally (Host Machine):** Packages will appear inside the container. However, this may cause issues if your local PHP version differs from the Docker environment.
- **Installing via Docker (Recommended):** Use `docker exec -it danangtrip_app composer require <package-name>`. This ensures 100% compatibility with the project's environment.
- **Troubleshooting:** If you experience errors after pulling code from Git or installing packages locally, run:
    ```bash
    docker exec -it danangtrip_app composer install
    ```

## Project Overview

**Topic**: BUILDING "DA NANG TRIP" TRAVEL WEBSITE - INTEGRATING A RATING AND CONTENT MANAGEMENT SYSTEM POWERED BY REWARD POINTS.

- **Backend Framework**: Laravel 12
- **Database**: Supabase (PostgreSQL)
- **Environment**: Docker
