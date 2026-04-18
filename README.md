# mmarchonimoveis-staging

Ambiente isolado de staging para o site `dev.mmarchonimoveis.com.br`.

## Estrutura

- `docker-compose.yml`: stack WordPress + MariaDB
- `.env`: credenciais locais do staging
- `app/`: arquivos do WordPress
- `scripts/`: scripts operacionais
- `exports/`: dumps e backups locais

## Comandos

```bash
cd /root/mmarchonimoveis-staging
docker compose up -d
docker compose down
```

## URL local

- `http://127.0.0.1:8090`

## Fluxo

1. Atualizar o staging.
2. Testar as mudanças.
3. Validar com o usuário.
4. Só então aplicar em produção.
