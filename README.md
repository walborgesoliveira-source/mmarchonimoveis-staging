# mmarchonimoveis-staging

Ambiente de staging do site `staging.mmarchonimoveis.com.br`, executado com Docker e preparado para testes antes de qualquer mudança em produção.

## Estrutura

- `docker-compose.yml`: stack WordPress + MariaDB
- `.env`: credenciais locais do staging
- `app/`: arquivos do WordPress
- `scripts/`: scripts operacionais
- `exports/`: dumps e backups locais
- `imagens/`: imagens de apoio usadas nas customizações visuais do tema e plugins
- `MD/`: documentação operacional complementar

## URLs

- Local: `http://127.0.0.1:8090`
- Staging público: `https://staging.mmarchonimoveis.com.br`
- Login WordPress: `https://staging.mmarchonimoveis.com.br/wp-login.php`
- Portal do Corretor: `https://staging.mmarchonimoveis.com.br/marchon-crm/`

## Tema Ativo

- Tema filho: `marchon-child` (pai: `twentytwentyfive`)
- Localização: `app/wp-content/themes/marchon-child/`

### Customizações aplicadas

- Identidade visual completa (header, menu, footer, tipografia)
- CPT `imoveis` com archive em `/imoveis/`
- Feed do Instagram integrado (plugin Smash Balloon)
- Menu principal com link **Portal do Corretor** injetado via `wp_nav_menu_items`

## Plugin Marchon CRM

Localização: `app/wp-content/plugins/marchon-crm/`

### Arquivos principais

- `marchon-crm.php`: bootstrap do plugin
- `includes/class-marchon-crm.php`: toda a lógica PHP
- `assets/crm-app.css`: design system do frontend (v1.0)
- `assets/crm-app.js`: comportamentos do frontend
- `assets/admin.js`: comportamentos do admin
- `assets/crm-mockup.png`: imagem do mockup usada na landing page

### Funcionalidades

- Post type `mcrm_client` com metadados completos (CPF, telefone, email, interesse, região, faixa de valor, corretor responsável, campos de terreno)
- **Landing page pública** (`/marchon-crm/`) com hero, seção de funcionalidades e gate de login — visível para qualquer visitante, conteúdo restrito a usuários autenticados
- **Workspace do corretor** após login: sidebar escura, topbar de busca, stat cards, gráficos de funil, lista de clientes e formulário de cadastro
- Pipeline por status: Novo → Em atendimento → Proposta → Convertido → Arquivado
- Filtros combinados (nome, CPF, telefone, tipo, status)
- Painel admin com relatórios de demanda por terreno
- Cada corretor vê apenas sua carteira; administradores têm visão completa
- Redirecionamento automático de corretores para o frontend (sem acesso ao wp-admin)
- Shortcode: `[marchon_crm_app]`

## Docker

```bash
cd /root/mmarchonimoveis-staging
docker compose up -d
docker compose down
docker compose ps
```

## Git e GitHub

- Branch principal: `main`
- Remote `origin`: `git@github.com:walborgesoliveira-source/mmarchonimoveis-staging.git`

```bash
cd /root/mmarchonimoveis-staging
git status
git add app/wp-content/plugins/marchon-crm/... app/wp-content/themes/marchon-child/...
git commit -m "Descreva a alteração"
git push origin main
```

> Nunca usar `git add .` — a pasta `uploads/` não deve ser versionada.

## Deploy

Script disponível em `scripts/deploy.sh`:

```bash
cd /root/mmarchonimoveis-staging
./scripts/deploy.sh
```

Executa:

```bash
git pull
docker compose up -d
```

## Operação

```bash
# Subir ou recriar a stack
docker compose up -d

# Backup do banco
./scripts/backup.sh

# Checar containers e cabeçalhos HTTP
./scripts/check-staging.sh
```

## Regras

- Nunca editar produção diretamente
- Sempre validar no staging antes de publicar
- Não versionar `.env`, dumps, uploads ou backups
- Não expor serviços do banco publicamente

## Fluxo

1. Fazer alterações no staging
2. Testar o comportamento no site
3. Validar o resultado
4. Commitar apenas arquivos de código (tema, plugins)
5. Push para GitHub
6. Replicar em produção
