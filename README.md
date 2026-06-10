# DA NANG TRIP - Graduation Project

## 1. Getting Started with Docker

This project is containerized using Docker and connects to a Supabase (PostgreSQL) database.

## DanangTrip Seeded Database Commands

Use these PowerShell commands from Windows when you want to run the curated DanangTrip dataset stored in `D:\DATN\DATN_Tài liệu`.

Update the current database without wiping all data:

```powershell
powershell -ExecutionPolicy Bypass -File "D:\DATN\DATN_Tài liệu\data-center\database-refresh\RUN_INCREMENTAL_UPDATE.ps1"
```

Rebuild the database from scratch. This backs up the current database, runs `migrate:fresh`, applies the full seed manifest, syncs schedule availability, audits data quality, and runs the schedule test:

```powershell
powershell -ExecutionPolicy Bypass -File "D:\DATN\DATN_Tài liệu\data-center\database-refresh\RUN_REBUILD_DATABASE.ps1"
```

Audit only:

```powershell
powershell -ExecutionPolicy Bypass -File "D:\DATN\DATN_Tài liệu\data-center\database-refresh\RUN_AUDIT_DATABASE.ps1"
```

### Standby Backup Database

The standby Supabase database has been prepared with the current schema and seed data. It is not used by the application by default.

To test the standby connection temporarily:

```powershell
$env:BACKUP_DB_HOST="aws-1-ap-southeast-1.pooler.supabase.com"
$env:BACKUP_DB_PORT="6543"
$env:BACKUP_DB_DATABASE="postgres"
$env:BACKUP_DB_USERNAME="postgres.aevuyguxwlcglpxcuwbe"
$env:BACKUP_DB_SSLMODE="require"
php artisan db:health-check --connections=pgsql_backup
```

If the primary database has a problem, manually switch Render's primary database env values to the standby database values, then redeploy the API. Do not run the app against both databases at the same time.

Recommended Render failover values:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.aevuyguxwlcglpxcuwbe
DB_PASSWORD=<same-as-primary-db-password>
DB_SSLMODE=require
```

If Render uses `DATABASE_URL`, replace it with:

```env
DATABASE_URL=postgresql://postgres.aevuyguxwlcglpxcuwbe:<same-as-primary-db-password>@aws-1-ap-southeast-1.pooler.supabase.com:6543/postgres?sslmode=require
```

### Chatbot Knowledge + Vector RAG

The chatbot uses a derived knowledge table, `chat_knowledge_base`, built from real project data:

- `tours`
- `locations`
- `blog_posts`
- DanangTrip policies/support settings

This table is not the source of truth. Prices, status, schedules, and public content still come from the real application tables. `chat_knowledge_base` is only an AI search index.

When running normal seeders, chatbot knowledge is rebuilt automatically because `ChatKnowledgeBaseSeeder` is called at the end of `DatabaseSeeder`:

```powershell
php artisan migrate:fresh --seed
```

However, vector embeddings are not generated inside the seeder. This is intentional because embedding calls use external AI API quota and can take several minutes. After a full database reset, run:

```powershell
php artisan chatbot:sync-knowledge --embed
```

Recommended full local rebuild flow:

```powershell
php artisan migrate:fresh --seed
php artisan chatbot:sync-knowledge --embed
php artisan test
```

If you only changed tour/location/blog/setting data and want to refresh the chatbot index without regenerating embeddings:

```powershell
php artisan chatbot:sync-knowledge
```

If you want to regenerate embeddings only for missing items:

```powershell
php artisan chatbot:sync-knowledge --embed
```

If you want to force regenerate all embeddings:

```powershell
php artisan chatbot:sync-knowledge --embed --force
```

For a small safe test run:

```powershell
php artisan chatbot:sync-knowledge --embed --limit=3
```

Docker equivalents:

```bash
docker exec -it danangtrip_app php artisan migrate:fresh --seed
docker exec -it danangtrip_app php artisan chatbot:sync-knowledge --embed
docker exec -it danangtrip_app php artisan test
```

Required chatbot/vector environment keys:

```env
CHATBOT_ENABLED=true
CHATBOT_VECTOR_ENABLED=true
CHATBOT_EMBEDDING_PROVIDER_ORDER=gemini,openai
GEMINI_EMBEDDING_MODEL=gemini-embedding-001
GEMINI_EMBEDDING_OUTPUT_DIMENSIONALITY=768
```

After changing `.env`, clear Laravel config cache:

```powershell
php artisan config:clear
```

Canonical seed/data folders:

- `D:\DATN\DATN_Tài liệu\database-seeders`
- `D:\DATN\DATN_Tài liệu\data-center\database-refresh`
- `D:\DATN\DATN_Tài liệu\data-center\collected-data`

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

## Gmail SMTP for Notifications

Admin notifications are saved in the database and also sent to the recipient email through Laravel Mail. To send through Gmail, add these values to `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-gmail-address@gmail.com
MAIL_FROM_NAME="Da Nang Trip"
FRONTEND_URL=http://localhost:3000
```

Use a Google App Password, not the normal Gmail login password. After changing `.env`, clear config cache:

```bash
docker exec -it danangtrip_app php artisan config:clear
```

## SePay VietQR Payment

The booking payment flow supports VietQR bank transfer with SePay IPN:

1. Customer creates a booking and starts payment.
2. API creates a pending payment and returns a VietQR image.
3. Customer transfers with content `DNT {booking_code}`.
4. SePay calls the IPN URL.
5. API verifies the booking code and amount, then updates:
    - `payments.payment_status = success`
    - `bookings.payment_status = success`
    - `bookings.booking_status = confirmed`

Add these values to `.env`:

```env
SEPAY_MERCHANT_ID=your-sepay-merchant-id
SEPAY_SECRET_KEY=your-sepay-secret-key
SEPAY_ENV=sandbox
SEPAY_VERIFY_IPN_SIGNATURE=false
SEPAY_PAYMENT_PREFIX=DNT

VIETQR_BANK_CODE=your-vietqr-bank-code
VIETQR_ACCOUNT_NO=your-bank-account-number
VIETQR_ACCOUNT_NAME="YOUR BANK ACCOUNT NAME"
VIETQR_TEMPLATE=compact2
VIETQR_IMAGE_BASE_URL=https://img.vietqr.io/image
```

Set this IPN URL in SePay:

```text
https://your-api-domain.com/api/v1/sepay/ipn
```

For local testing, expose the API with ngrok/cloudflared and use:

```text
https://your-tunnel-domain.ngrok-free.app/api/v1/sepay/ipn
```

After changing `.env`, clear config cache:

```bash
docker exec -it danangtrip_app php artisan config:clear
```
