#!/usr/bin/env bash
# Scheduler: ten sam obraz co backend, uruchamia cron co minute.
set -euo pipefail
export -p > /etc/turysta.env
printf 'SHELL=/bin/bash\nPATH=/usr/local/bin:/usr/bin:/bin\n* * * * * root . /etc/turysta.env; cd /var/www/html && php bin/tick.php >> /proc/1/fd/1 2>&1\n' > /etc/cron.d/turysta
chmod 0644 /etc/cron.d/turysta
exec cron -f
