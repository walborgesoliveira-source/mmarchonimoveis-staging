#!/bin/bash
set -euo pipefail

cd /root/mmarchonimoveis-staging

git pull
docker compose up -d
