# eDoc deployment guide (Render + private GitHub)

This repository keeps the original PHP/MySQL eDoc application. It does not migrate the database to PostgreSQL and does not introduce a PHP framework.

Application root: `doctor-appointment-system/`  
Entry point: `index.html`  
Database dump: `doctor-appointment-system/sql_database_edoc.sql`  
Database name: `SQL_Database_edoc`

## MySQL strategy

Render does not offer managed MySQL.

| | A. MariaDB as a Render Private Service | B. External managed MySQL |
|---|---|---|
| Cost | Paid web/private service + persistent disk | Provider plan; some have a small free/trial tier |
| Reliability | You operate MySQL yourself on Render | Provider operates HA, patching, failover |
| Persistence | Requires a persistent disk mounted at `/var/lib/mysql` | Provider storage |
| Backups | You must configure dumps/snapshots | Usually built-in |
| Security | Keep the DB private (no public port). App talks over the private network | Restrict to the Render egress IPs or SSL |
| Ease | More moving parts on Render | Simpler app service; one connection string |

**Recommended default: option B (external managed MySQL/MariaDB)** such as Aiven, DigitalOcean, Railway, or Clever Cloud. It is the most practical way to keep the original MySQL engine without running a database server on Render.

Use option A only if every component must stay on Render. Then:

1. Create a **Private Service** from `mariadb:10.11`.
2. Attach a **persistent disk** and mount it at `/var/lib/mysql`.
3. Set `MYSQL_DATABASE=SQL_Database_edoc`, `MYSQL_ROOT_PASSWORD`, and a dedicated app user through environment variables.
4. Do **not** publish port 3306 publicly.
5. Point the web service `DB_HOST` at the private service hostname.
6. Do **not** put the SQL dump in `/docker-entrypoint-initdb.d` of a rebuilt image. Import once onto the persistent disk.

## One-time database import

The dump is a phpMyAdmin export of demo data. Do not import it on every deploy. Do not overwrite an existing production database.

1. Create an empty database named `SQL_Database_edoc`.
2. Confirm the target is empty (`SHOW TABLES;` returns nothing).
3. Import **once** from a trusted machine:

```bash
mysql -h HOST -P 3306 -u USER -p SQL_Database_edoc < doctor-appointment-system/sql_database_edoc.sql
```

4. Confirm tables: `admin`, `doctor`, `patient`, `webuser`, `schedule`, `appointment`, `specialties`.
5. Change every demo password before public use. Demo accounts in the dump are not production credentials:

- `admin@edoc.com` / `123`
- `doctor@edoc.com` / `123`
- `patient@edoc.com` / `123`

Do not commit additional dumps that contain real patient records.

## GitHub (private repository)

1. Create a **private** GitHub repository.
2. Do not commit `.env`, `.local-runtime/`, zip files, or extra database backups.
3. Push the repository, then connect it in Render.

## Render web service

1. New Web Service from the private GitHub repo.
2. Runtime: **Docker**.
3. Dockerfile path: `Dockerfile`.
4. Context: repository root (not `doctor-appointment-system/`).
5. Set environment variables before the first deploy.

### Environment variables

| Variable | Example | Notes |
|---|---|---|
| `PORT` | set by Render | Apache listens on `0.0.0.0:$PORT` |
| `DB_HOST` | MySQL hostname | Private hostname or managed host |
| `DB_PORT` | `3306` | |
| `DB_NAME` | `SQL_Database_edoc` | Keep the original name |
| `DB_USER` | app user | Not committed to Git |
| `DB_PASSWORD` | secret | Render dashboard only |

6. Health check path: `/health.php`
7. After deploy, open `/` and `/health.php`.

Expected health response:

```json
{"ok":true,"app":"edoc","php":true,"database":true}
```

`{"ok":false,"database":false}` means PHP is up but MySQL is not reachable. The response never includes credentials.

## Local Docker (optional)

From `doctor-appointment-system/`:

```bash
docker compose up --build
```

Then import the SQL dump once into the `db` service. The web app is at `http://localhost:8080/`. Local XAMPP still works: `connection.php` defaults to `localhost`, `root`, empty password, and `SQL_Database_edoc` when env vars are unset.

## Troubleshooting

| Symptom | Check |
|---|---|
| Deploy crash / port error | App must listen on `0.0.0.0` and `$PORT` |
| Homepage 404 | Docker context must be the repository root |
| `Database connection failed.` | `DB_HOST`, user, password, firewall/SSL, database name |
| Health check 503 | MySQL not imported or not reachable |
| Login fails after import | User exists in both `webuser` and the role table |
| Lost sessions | Render free instances sleep; PHP sessions are local to the instance |
| SQL dump downloadable | Production image excludes `*.sql` via `.dockerignore` |

## Security notes (existing application)

These issues exist in the original eDoc code. They were not silently rewritten:

- Passwords are stored and compared as plain text.
- Many queries concatenate user input (SQL injection risk).
- Session checks exist, but some doctor/patient delete scripts still require admin role `a`.
- Demo credentials in the SQL dump must be rotated before any public use.
- Admin pages are protected by session role checks, not by a separate firewall.

Safer follow-up work (separate change): password hashing, prepared statements on remaining queries, CSRF tokens, HTTPS-only cookies, and replacing demo accounts.
