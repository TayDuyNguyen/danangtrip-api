# DA NANG TRIP - Graduation Project

## 1. How to Run with Docker

This project is configured to run with Docker and connect to a Supabase database.

### Prerequisites

- Docker & Docker Compose installed on your machine.
- A `.env` file configured with your Supabase credentials.

### Essential Docker Commands

**1. Start/Stop the Project:**

```bash
# Start
docker-compose up -d

# Stop
docker-compose down
```

**2. Database Management (Crucial for DATN):**

```bash
# Run new migrations
docker exec -it danangtrip_app php artisan migrate

# RESET EVERYTHING (Delete all data and run migrations from scratch)
docker exec -it danangtrip_app php artisan migrate:fresh

# RESET EVERYTHING + SEED (Run migrations and populate dummy data)
docker exec -it danangtrip_app php artisan migrate:fresh --seed

# Check migration status
docker exec -it danangtrip_app php artisan migrate:status
```

**3. Useful Development Commands:**

```bash
# Clear all Cache (Fix "not receiving updates" issues)
docker exec -it danangtrip_app php artisan optimize:clear

# List all API Routes
docker exec -it danangtrip_app php artisan route:list

# Create new Model + Migration + Controller
docker exec -it danangtrip_app php artisan make:model YourModel -mc

# Access the Container Shell
docker exec -it danangtrip_app bash
```

**4. Rebuild the Image:**

```bash
# Needed if you change Dockerfile or nginx.conf
docker-compose up -d --build
```

## Project Description

**Topic**: BUILDING DA NANG TRAVEL WEBSITE "DA NANG TRIP" - INTEGRATING RATING AND CONTENT MANAGEMENT SYSTEM USING POINT.

- **Backend**: Laravel 12
- **Database**: Supabase (PostgreSQL)
- **Environment**: Docker
