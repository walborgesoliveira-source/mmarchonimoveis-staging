# mmarchonimoveis-staging

Ambiente isolado de staging do site `dev.mmarchonimoveis.com.br`, executado com Docker e preparado para testes antes de qualquer mudança em produção.

## Estrutura

- `docker-compose.yml`: stack WordPress + MariaDB
- `.env`: credenciais locais do staging
- `app/`: arquivos do WordPress
- `scripts/`: scripts operacionais
- `exports/`: dumps e backups locais
- `imagens/`: imagens de apoio usadas nas customizações visuais do tema
- `MD/`: documentação operacional complementar

## URLs

- Local: `http://127.0.0.1:8090`
- Staging público: `https://staging.mmarchonimoveis.com.br`
- Login WordPress: `https://staging.mmarchonimoveis.com.br/wp-login.php`

## Docker

```bash
cd /root/mmarchonimoveis-staging
docker compose up -d
docker compose down
docker compose ps
```

## Git E GitHub

- Branch principal: `main`
- Remote `origin`: `git@github.com:walborgesoliveira-source/mmarchonimoveis-staging.git`

Comandos úteis:

```bash
cd /root/mmarchonimoveis-staging
git status
git add .
git commit -m "Descreva a alteracao"
git push origin main
```

## Deploy

Script disponível em `scripts/deploy.sh`:

```bash
cd /root/mmarchonimoveis-staging
./scripts/deploy.sh
```

Esse script executa:

```bash
git pull
docker compose up -d
```

## Operacao

Subir ou recriar a stack:

```bash
cd /root/mmarchonimoveis-staging
docker compose up -d
```

Gerar backup do banco do staging em `exports/`:

```bash
cd /root/mmarchonimoveis-staging
./scripts/backup.sh
```

Checar containers e cabecalhos HTTP do staging local e publico:

```bash
cd /root/mmarchonimoveis-staging
./scripts/check-staging.sh
```

## Regras

- Nunca editar produção diretamente
- Sempre validar no staging antes de publicar
- Não versionar `.env`, dumps ou backups
- Não expor serviços do banco publicamente

## Fluxo

1. Fazer alterações no staging.
2. Testar o comportamento no site.
3. Validar o resultado.
4. Commitar e enviar para o GitHub.
5. Só depois replicar em produção.
