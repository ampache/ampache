# Ampache and docker (local dev environment)

Make sure you check out the pre-built docker images first if you just want to *run* Ampache:

https://github.com/ampache/ampache-docker

This setup is different: it is a **live development environment**. The whole repo is
bind-mounted into the container, so you edit code on your host and refresh the browser to
see the change — no rebuild required. A MariaDB service is included, so everything you need
comes up with a single command.

## Quick start

From the repo root:

```shell
docker compose up --build
```

Then open http://localhost:8084

On the first run the container will:

1. create the `public/.htaccess`, `public/play/.htaccess` and `public/rest/.htaccess`
   files from their `.dist` templates,
2. run `composer install` / `npm run build` only if `vendor/` or `public/dist` are missing
   (if you already built on the host they are reused),
3. wait for the database and auto-install Ampache, creating an admin account.

Default admin login (change it): **admin / admin**

To stop:

```shell
docker compose down          # keep the database
docker compose down -v       # also wipe the database volume (fresh install next time)
```

## Services

| Service   | Container    | Port (host) | Notes                                        |
|-----------|--------------|-------------|----------------------------------------------|
| `ampache` | `ampache`    | `8084`      | Apache + PHP 8.5, repo mounted at `/var/www/html` |
| `db`      | `ampache-db` | `3306`      | MariaDB, data persisted in the `db-data` volume   |

## Configuration

Everything is driven by environment variables with sensible defaults, so `docker compose up`
works with no extra setup. To override, create a `.env` file in the repo root:

```dotenv
DB_NAME=ampache
DB_USER=root                 # admin creds used to create the schema
DB_PASSWORD=ampache
AMPACHE_DB_USER=ampache      # app creds written into config/ampache.cfg.php
AMPACHE_DB_PASSWORD=ampache
AMPACHE_ADMIN_USER=admin
AMPACHE_ADMIN_PASSWORD=admin
AMPACHE_ADMIN_EMAIL=admin@example.com
```

### Prefer the web installer?

Set `DB_NAME=` (empty) in your `.env` to skip the automatic install. Ampache will then send
you to the web installer at http://localhost:8084; use these values when it asks:

* Database Host: `db`
* Database Port: `3306`
* Username / Password (admin, to create the DB): `root` / `ampache`
* Ampache Database User / Password: `ampache` / `ampache`

## Media and logs

* Put media under `docker/media/` on the host — it appears at `/media` in the container.
  When adding a catalog in Ampache, set the path to `/media`.
* Apache/Ampache logs are written to `docker/log/` on the host.

## Notes

* The database runs as a **separate service**, not inside the Ampache container, so the
  container image no longer bundles `mariadb-server`.
* `composer` is available inside the container (`docker compose exec ampache composer ...`)
  and dev-tuned PHP settings live in `docker/data/php/99-ampache-dev.ini`.
