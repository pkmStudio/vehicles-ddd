# dan-vehicles Runbook

## Local Services

Start the local stack:

```bash
docker compose up -d
```

Check service state:

```bash
docker compose ps
```

Run workers:

```bash
php artisan horizon
```

## RabbitMQ

Apply project bindings after deploy or config changes:

```bash
php artisan rabbit-transport:setup
```

Check the expected routes:

- `crm.* -> vehicles.inbox`
- `vehicles.# -> crm.inbox`
- `warehouse.# -> crm.inbox`
- `applicability.# -> crm.inbox`

For local debugging, inspect queue workers and RabbitMQ container logs through Docker Compose.

## S3

Required environment keys:

```dotenv
S3_ENDPOINT_URL=
S3_BUCKET=
S3_ACCESS_KEY=
S3_SECRET_KEY=
S3_REGION=us-east-1
S3_USE_PATH_STYLE_ENDPOINT=true
S3_EXPORTS_ROOT=exports
```

Smoke-check file access through the feature that owns the flow: import file request, export file
request, or cleanup listener. Do not bypass feature adapters with ad hoc storage calls in
Application code.

## dan-center Integration

REST read API:

- `GET /api/v1/catalog/*` uses `DAN_CATALOG_READ_API_KEY` via `X-Service-Key`.
- `GET /api/v1/crm/*` uses `DAN_VEHICLES_READ_API_KEY` via `X-Service-Key`.

Rabbit write/heavy flows use `operation_id` for correlation:

- import/export requests;
- catalog mutations;
- applicability calculation requests;
- result events back to CRM.

## Verification

Architecture and dependency gate:

```bash
php artisan test tests/Feature/Architecture
```

Targeted REST tests:

```bash
php artisan test tests/Feature/Vehicles/Catalog tests/Feature/Warehouse/Catalog
```

Full feature tests require PostgreSQL to be reachable as configured by the test environment.
