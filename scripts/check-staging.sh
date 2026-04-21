#!/bin/bash
set -euo pipefail

cd /root/mmarchonimoveis-staging

echo "== Docker =="
docker compose ps

echo
echo "== WordPress local =="
curl -sS -I http://127.0.0.1:8090 | sed -n '1,10p'

echo
echo "== WordPress publico =="
curl -k -sS -I https://staging.mmarchonimoveis.com.br | sed -n '1,10p'
