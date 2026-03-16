# DA NANG TRIP - Graduation Project

## 1. How to Run with Docker

This project is configured to run with Docker and connect to a Supabase database.

### Prerequisites
- Docker & Docker Compose installed on your machine.
- A `.env` file configured with your Supabase credentials.

### Essential Docker Commands

**1. Start the Project:**

Run this command to build and start the `app` and `nginx` containers in the background.
```bash
docker-compose up -d
```
- Your website will be available at: [http://localhost:8000](http://localhost:8000)

---

**2. Run Database Migrations:**

To create or update your database tables on Supabase.
```bash
docker exec -it danangtrip_app php artisan migrate
```

---

**3. Stop the Project:**

To stop all running containers for this project.
```bash
docker-compose down
```

---

**4. Access the Container Shell:**

If you need to run other `php artisan` commands (e.g., `make:model`, `config:clear`).
```bash
docker exec -it danangtrip_app bash
```

---

**5. Rebuild the Image:**

Only needed if you make changes to the `Dockerfile`.
```bash
docker-compose build
```

## Project Description
**Topic**: BUILDING DA NANG TRAVEL WEBSITE "DA NANG TRIP" - INTEGRATING RATING AND CONTENT MANAGEMENT SYSTEM USING POINT.

- **Backend**: Laravel 12
- **Database**: Supabase (PostgreSQL)
- **Environment**: Docker
