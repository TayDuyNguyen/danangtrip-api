# Redis Deployment Notes

Use Redis for production cache, sessions, queues, and Laravel rate limiting. Keep PostgreSQL/Supabase for business data only.

## Production environment

Set these variables in the hosting platform:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=predis
REDIS_URL=rediss://default:your-redis-password@your-redis-host:6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
```

`rediss://` is the TLS Redis URL used by many managed providers such as Upstash and Redis Cloud. If your provider gives separate host, port, username, and password values, you can use `REDIS_HOST`, `REDIS_PORT`, `REDIS_USERNAME`, and `REDIS_PASSWORD` instead.

## Deploy commands

Run after changing environment variables:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

If `QUEUE_CONNECTION=redis`, run a queue worker as a separate process:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --timeout=90
```

On a VPS, keep the worker alive with Supervisor. On Railway, Render, Fly.io, or similar platforms, create a separate worker service that runs the command above.

## Local development

For local development, this project uses:

```env
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

This keeps local API requests from touching the remote PostgreSQL database just to read cache, session, or rate-limit data.
