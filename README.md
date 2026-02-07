# cdm-media-request

## Dev stack

Start:
```bash
docker compose up -d --build
```

Stop:
```bash
docker compose down
```

## Ports
- Public form: http://localhost:8001
- Adminer: http://localhost:8082
- Mailpit UI: http://localhost:8026
- MySQL (host): `localhost:3308` (user: `appuser`, pass: `apppass123`, db: `appdb_dev`)

## Email testing
Mailpit is wired for local SMTP:
- SMTP host: `mailpit`
- SMTP port: `1025`

## Structure
- `apps/` application code
- `db/` MySQL init/seed scripts
- `docker/` docker build assets
- `docs/` project documentation
- `sql/` ad-hoc SQL scripts and legacy dumps
