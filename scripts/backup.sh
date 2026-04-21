#!/bin/bash
set -euo pipefail

cd /root/mmarchonimoveis-staging

if [[ ! -f .env ]]; then
    echo "Arquivo .env nao encontrado em /root/mmarchonimoveis-staging" >&2
    exit 1
fi

set -a
source ./.env
set +a

timestamp="$(date +%Y%m%d-%H%M%S)"
backup_file="exports/backup-${timestamp}.sql"

mkdir -p exports

docker exec mmarchonimoveis_staging_db sh -lc \
    "exec mysqldump -u root -p\"${MYSQL_ROOT_PASSWORD}\" \"${MYSQL_DATABASE}\"" \
    > "${backup_file}"

echo "Backup gerado em ${backup_file}"
