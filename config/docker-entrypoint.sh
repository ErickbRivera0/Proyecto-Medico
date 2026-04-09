#!/bin/sh
# docker-entrypoint.sh
#
# 1. Run the database collation migration (idempotent).
# 2. Start the application via supervisord.
#
# The migration is attempted with a short retry loop to handle the case where
# the MySQL service is still warming up when the container starts.

set -e

MAX_RETRIES=10
RETRY_DELAY=3

echo "[entrypoint] Starting pre-deploy migration …"

i=1
while [ "$i" -le "$MAX_RETRIES" ]; do
    if php /usr/local/bin/migrate.php; then
        echo "[entrypoint] Migration succeeded."
        break
    else
        EXIT_CODE=$?
        # Exit code 1 from migrate.php means either a connection failure or a
        # collation mismatch that could not be fixed.  Retry on connection
        # errors; after all retries, warn but continue so the app can still
        # start (the collation issue is non-fatal for most queries).
        echo "[entrypoint] Migration attempt ${i}/${MAX_RETRIES} exited with code ${EXIT_CODE}."
        if [ "$i" -eq "$MAX_RETRIES" ]; then
            echo "[entrypoint] WARNING: Migration did not complete cleanly after ${MAX_RETRIES} attempts. Continuing anyway."
        else
            echo "[entrypoint] Retrying in ${RETRY_DELAY}s …"
            sleep "$RETRY_DELAY"
        fi
    fi
    i=$((i + 1))
done

echo "[entrypoint] Handing off to supervisord …"
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
