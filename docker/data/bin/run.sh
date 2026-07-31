#!/bin/bash
#
# Dev-environment entrypoint for Ampache (develop).
#
# The whole repo is bind-mounted at /var/www/html so you edit code live on the
# host and refresh the browser. This script only prepares the bits that are not
# committed to git (htaccess, vendor, built assets) and optionally installs the
# database on first run, then hands off to supervisord (apache).

set -e

APP_DIR=/var/www/html
PUBLIC_DIR="$APP_DIR/public"
CONFIG_FILE="$APP_DIR/config/ampache.cfg.php"

echo "=> Preparing Ampache dev environment"

# Runtime directories that must exist
mkdir -p /var/log/supervisor /var/log/ampache

# Create the .htaccess files from their .dist templates (rewrite rules for the
# app root, streaming and the REST API). These are gitignored so may be absent.
for sub in "" "play/" "rest/"; do
    dist="${PUBLIC_DIR}/${sub}.htaccess.dist"
    dest="${PUBLIC_DIR}/${sub}.htaccess"
    if [ -f "$dist" ] && [ ! -f "$dest" ]; then
        echo "=> Creating public/${sub}.htaccess"
        cp "$dist" "$dest"
    fi
done

# Install PHP dependencies if the mounted checkout has none
if [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "=> vendor/ missing - running composer install (first run can be slow)"
    ( cd "$APP_DIR" && composer install --prefer-dist --no-interaction --no-progress )
fi

# Build the frontend assets if they are missing
if [ ! -d "$PUBLIC_DIR/dist/assets" ]; then
    echo "=> public/dist missing - installing npm packages and building assets"
    ( cd "$APP_DIR" && npm install --no-audit --no-fund && npm run build )
fi

# Optional zero-touch database install on first run.
# Runs only when there is no config yet and DB_* variables are provided.
# DB_USER/DB_PASSWORD are the *administrative* credentials used to create the
# database (root by default); AMPACHE_DB_USER/AMPACHE_DB_PASSWORD are the app
# credentials written into the generated config.
if [ ! -f "$CONFIG_FILE" ] && [ -n "$DB_HOST" ] && [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
    DB_PORT="${DB_PORT:-3306}"
    APP_DB_USER="${AMPACHE_DB_USER:-$DB_USER}"
    APP_DB_PASSWORD="${AMPACHE_DB_PASSWORD:-$DB_PASSWORD}"

    echo "=> No config found - waiting for database ${DB_HOST}:${DB_PORT}"
    for _ in $(seq 1 30); do
        if php -r '$h=getenv("DB_HOST");$p=(int)(getenv("DB_PORT")?:3306);exit(@fsockopen($h,$p,$e,$s,2)?0:1);'; then
            break
        fi
        sleep 2
    done

    echo "=> Installing Ampache database '${DB_NAME}' on ${DB_HOST}"
    if php "$APP_DIR/bin/installer" install \
        --dbhost "$DB_HOST" \
        --dbport "$DB_PORT" \
        --dbname "$DB_NAME" \
        --dbuser "$DB_USER" \
        --dbpassword "$DB_PASSWORD" \
        --ampachedbuser "$APP_DB_USER" \
        --ampachedbpassword "$APP_DB_PASSWORD" \
        --force; then

        # Apply any pending schema migrations so the first page is the login screen
        echo "=> Applying database migrations"
        php "$APP_DIR/bin/cli" admin:updateDatabase -e || true

        # Create an initial admin account if requested
        if [ -f "$CONFIG_FILE" ] && [ -n "$AMPACHE_ADMIN_USER" ]; then
            echo "=> Creating admin user '$AMPACHE_ADMIN_USER'"
            php "$APP_DIR/bin/cli" admin:addUser "$AMPACHE_ADMIN_USER" \
                -p "${AMPACHE_ADMIN_PASSWORD:-admin}" \
                -e "${AMPACHE_ADMIN_EMAIL:-admin@example.com}" \
                -l 100 || true
        fi
    else
        echo "=> Automatic install failed - complete setup in the web installer at http://localhost:8084"
    fi
fi

# Let apache (www-data) read/write config and logs
chown -R www-data:www-data /var/log/ampache "$APP_DIR/config" 2>/dev/null || true

echo "=> Starting services"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
