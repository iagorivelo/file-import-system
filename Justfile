# File Import System — atalhos de desenvolvimento
# Documentação: rode `just` para ver todos os comandos.

set dotenv-load := true

# Compose de produção (arquivo base + override)
prod := "docker compose -f docker-compose.yml -f docker-compose.prod.yml"

# Lista os comandos disponíveis
default:
    @just --list --unsorted

# --- Ciclo de vida dos containers -------------------------------------------

# Sobe todos os containers em background
up:
    docker compose up -d

# Sobe (re)construindo as imagens
up-build:
    docker compose up -d --build

# Derruba os containers
down:
    docker compose down

# Derruba os containers e REMOVE os volumes (apaga dados do banco!)
down-volumes:
    docker compose down -v

# Reinicia os containers
restart:
    docker compose restart

# (Re)constrói as imagens
build:
    docker compose build

# Status dos containers
ps:
    docker compose ps

# Logs em tempo real (ex.: just logs app | just logs queue)
logs *service:
    docker compose logs -f {{service}}

# --- Atalhos dentro do container --------------------------------------------

# Abre um shell no container da aplicação
shell:
    docker compose exec app bash

# Artisan (ex.: just artisan migrate)
artisan *args:
    docker compose exec app php artisan {{args}}

# Composer (ex.: just composer require pacote)
composer *args:
    docker compose exec app composer {{args}}

# NPM no container node (ex.: just npm install)
npm *args:
    docker compose run --rm node npm {{args}}

# Tinker
tinker:
    docker compose exec app php artisan tinker

# --- Banco de dados ---------------------------------------------------------

# Roda as migrations
migrate:
    docker compose exec app php artisan migrate

# Recria o banco do zero com seeders
fresh:
    docker compose exec app php artisan migrate:fresh --seed

# Roda os seeders
seed:
    docker compose exec app php artisan db:seed

# --- Qualidade --------------------------------------------------------------

# Testes (ex.: just test | just test -- --filter=Program)
test *args:
    docker compose exec app php artisan test {{args}}

# Pest direto (ex.: just pest -- --filter=Arch)
pest *args:
    docker compose exec app ./vendor/bin/pest {{args}}

# Pint (code style fixer)
pint *args:
    docker compose exec app ./vendor/bin/pint {{args}}

# --- Utilitários ------------------------------------------------------------

# Limpa todos os caches da aplicação
clear:
    docker compose exec app php artisan optimize:clear

# Worker de filas avulso (já existe o container 'queue')
queue:
    docker compose exec app php artisan queue:work

# Publica os assets do Filament
assets:
    docker compose exec app php artisan filament:assets:publish

# Compila os assets do front-end (tema do painel) com Vite
assets-build:
    docker compose run --rm node sh -lc "npm install && npm run build"

# Modo dev do Vite com hot-reload (recompila o tema ao salvar)
assets-dev:
    docker compose run --rm --service-ports node sh -lc "npm install && npm run dev -- --host"

# Cria o link simbólico do storage
storage-link:
    docker compose exec app php artisan storage:link

# Setup inicial completo (primeira execução do projeto)
setup:
    cp -n .env.example .env || true
    docker compose build
    docker compose up -d
    docker compose exec app composer install
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan storage:link
    docker compose exec app php artisan migrate --seed
    docker compose exec app php artisan filament:assets:publish
    docker compose run --rm node sh -lc "npm install && npm run build"
    @echo ""
    @echo "  Pronto! Painel admin:   http://localhost:8000/admin"
    @echo "          Painel usuário: http://localhost:8000/app"

# --- Produção ---------------------------------------------------------------

# Sobe a stack em produção (queue:work, restart always, portas de BD/Redis fechadas)
up-prod:
    {{prod}} up -d

# Sobe produção reconstruindo as imagens
up-prod-build:
    {{prod}} up -d --build

# Derruba a stack de produção
down-prod:
    {{prod}} down

# Status dos containers (produção)
ps-prod:
    {{prod}} ps

# Logs (produção) — ex.: just logs-prod queue
logs-prod *service:
    {{prod}} logs -f {{service}}

# Deploy: dependências, assets, migrações, caches e restart dos workers/OPcache
deploy:
    {{prod}} exec -T app composer install --no-dev --optimize-autoloader
    docker compose run --rm node sh -lc "npm ci && npm run build"
    {{prod}} exec -T app php artisan migrate --force
    {{prod}} exec -T app php artisan filament:assets:publish
    {{prod}} exec -T app php artisan config:cache
    {{prod}} exec -T app php artisan route:cache
    {{prod}} exec -T app php artisan view:cache
    {{prod}} exec -T app php artisan queue:restart
    {{prod}} restart app
    @echo "Deploy concluído."

# --- Demo / URL pública temporária ------------------------------------------

# Cria uma URL pública (Cloudflare Tunnel) apontando para o app local.
# A URL é temporária e muda a cada execução; vale enquanto o túnel estiver no ar.
tunnel:
    -docker rm -f fis-tunnel 2>/dev/null
    docker run -d --name fis-tunnel --network file-import-system_fis cloudflare/cloudflared:latest tunnel --url http://nginx:80
    @echo "Gerando URL pública (aguarde alguns segundos)..."
    @timeout 30 docker logs -f fis-tunnel 2>&1 | grep -m1 -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | sed 's#^#  URL pública: #'
    @echo "  Acesse /admin (admin) ou /app (usuário). Parar: just tunnel-stop"

# Derruba o túnel público
tunnel-stop:
    -docker rm -f fis-tunnel
