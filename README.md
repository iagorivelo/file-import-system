# File Import System

Sistema com painel administrativo onde usuários importam arquivos (`.txt` / `.csv`)
através de **programas** (boxes). Cada programa aponta para uma classe processadora
dedicada que lê e trata o arquivo de forma assíncrona.

- **Admin** (`/admin`): gerencia usuários (senha, ativar/inativar, tempo ativo/online),
  cadastra programas (boxes) e consulta o histórico de importações.
- **Usuário comum** (`/app`): vê as boxes disponíveis, clica em uma, envia um arquivo
  `.txt`/`.csv` e acompanha o status do processamento.

## Stack

| Item | Versão / Escolha |
|------|------------------|
| Framework | Laravel 13 |
| Painel | Filament v5 (painéis `admin` e `app`) |
| PHP | 8.5 |
| Banco | MySQL 8.4 |
| Cache / Sessão / Filas | Redis |
| Testes | Pest 4 (+ Pest Arch) |
| Infra | Docker Compose + Justfile |

## Arquitetura (DDD em camadas)

A lógica de negócio vive em `src/` (autoload PSR-4 `Src\`); a pasta `app/` é a "casca"
Laravel/Filament.

```
src/
  Domain/          # Regras puras: enums, contratos, value objects (sem framework)
    User/          #   UserRole
    Import/        #   FileProcessor (contrato), ProcessorRegistry, FileType,
                   #   ImportStatus, ImportContext, ImportResult, exceptions
  Application/     # Casos de uso (orquestração) e portas
    Import/        #   StartImport, RunImport, ImportDispatcher, FileStorage, DTOs
    User/          #   ChangeUserPassword, SetUserActivation
  Infrastructure/  # Implementações concretas
    Persistence/Models/   # Eloquent: User, Program, FileImport, UserSession
    Import/               # DirectoryProcessorRegistry + Processors/ (estratégias)
    Queue/                # ProcessFileImportJob, QueuedImportDispatcher
    Storage/              # LocalFileStorage

app/
  Filament/Admin/  # UserResource, ProgramResource, FileImportResource
  Filament/App/    # Pages/Programs (grade de boxes + importação)
  Providers/       # DomainServiceProvider (bindings), painéis
  Listeners/       # RecordSuccessfulLogin, RecordLogout (sessões)
  Http/Middleware/ # UpdateUserActivity (status online)
```

As regras de camada são garantidas por testes de arquitetura (Pest Arch):
o domínio não conhece framework, e a aplicação não conhece a apresentação.

## O coração do sistema: processadores (Strategy)

Cada programa cadastrado no admin aponta para uma classe que implementa
`Src\Domain\Import\FileProcessor`. Ao importar um arquivo daquele programa, o sistema
resolve a classe e executa o processamento numa fila (Redis).

**Para criar uma nova rotina de importação**, basta criar uma classe em
`src/Infrastructure/Import/Processors/` — ela aparece automaticamente na lista de
seleção ao cadastrar/editar um programa:

```php
namespace Src\Infrastructure\Import\Processors;

use Src\Domain\Import\{FileProcessor, FileType, ImportContext, ImportResult};

final class MeuProcessador implements FileProcessor
{
    public static function label(): string
    {
        return 'Meu Programa';
    }

    /** Restringe os tipos aceitos pelo programa (modal de import respeita isto). */
    public static function acceptedFileTypes(): array
    {
        return [FileType::Txt, FileType::Csv];
    }

    public function process(ImportContext $context): ImportResult
    {
        // $context->filePath  -> caminho absoluto do arquivo
        // $context->type      -> FileType::Txt | FileType::Csv
        // ... leitura e tratamento ...

        return new ImportResult(processedRows: 100, failedRows: 0);
    }
}
```

Cada processador declara em `acceptedFileTypes()` os formatos que aceita; o modal de
importação só oferece esses tipos.

Já vem o `TesteProcessor` — o **programa "Teste"** semeado, que aceita apenas `.txt`
e grava cada linha (nome) na tabela `testes_tb` (`id`, `nome`, `created_at`).

## Como rodar

Pré-requisitos: Docker, Docker Compose e [`just`](https://github.com/casey/just).

```bash
just setup     # primeira vez: build, sobe, instala, migra e popula o banco
# ou, se já configurado:
just up
```

Acesse:

- Painel admin: <http://localhost:8000/admin>
- Painel usuário: <http://localhost:8000/app>

### Credenciais (seed)

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Administrador | `admin@fileimport.local` | `password` |
| Usuário comum | `usuario@fileimport.local` | `password` |

## Comandos (Justfile)

| Comando | Descrição |
|---------|-----------|
| `just up` | Sobe os containers |
| `just down` | Derruba os containers |
| `just up-build` | Sobe reconstruindo as imagens |
| `just restart` | Reinicia os containers |
| `just ps` | Status dos containers |
| `just logs [serviço]` | Logs em tempo real (ex.: `just logs queue`) |
| `just shell` | Shell no container da aplicação |
| `just artisan <args>` | Artisan (ex.: `just artisan migrate`) |
| `just composer <args>` | Composer |
| `just npm <args>` | NPM (container node) |
| `just migrate` / `just fresh` | Migrations / recriar com seed |
| `just test [args]` | Testes (Pest) |
| `just pint` | Code style (Pint) |
| `just clear` | Limpa caches |
| `just assets-build` | Compila o tema do painel (Vite) |
| `just assets-dev` | Vite em modo dev (hot-reload do tema) |
| `just setup` | Setup inicial completo (inclui o build) |

> Para passar flags aos testes: `just test -- --filter=Import`.

## Front-end / tema do painel

O painel do usuário (`app`) usa um **tema Filament próprio** (Tailwind v4 + Vite) em
`resources/css/filament/app/theme.css`, registrado via `->viteTheme(...)`. Isso permite
escrever classes Tailwind direto nas views customizadas (sem CSS inline).

- Os assets são compilados com `just assets-build` (já incluso no `just setup`).
- Ao adicionar **novas classes Tailwind** em uma view customizada, rode `just assets-build`
  novamente — ou use `just assets-dev` para recompilar automaticamente ao salvar.

## Testes

```bash
just test
```

Cobre: arquitetura (camadas DDD), enums/value objects do domínio, descoberta de
processadores, pipeline de importação ponta a ponta, controle de acesso aos painéis
e gestão de usuários. Os testes usam SQLite em memória e fila síncrona.

## Serviços (Docker)

`app` (PHP-FPM 8.5) · `nginx` · `mysql` · `redis` · `queue` (worker) ·
`scheduler` · `node` (perfil `dev`, para o Vite).

> **Filas em dev vs. produção:** em desenvolvimento o worker usa `queue:listen`
> (reinicia a aplicação a cada job, então mudanças no código valem na hora). Em
> produção usa-se `queue:work` (mais rápido), e o código novo é aplicado no deploy
> com `php artisan queue:restart`.

## URL pública temporária (demo)

Para mostrar o app rodando localmente numa URL pública (HTTPS), sem hospedar:

```bash
just tunnel        # cria um Cloudflare Tunnel e imprime a URL (*.trycloudflare.com)
just tunnel-stop   # encerra o túnel
```

A URL é **temporária** (muda a cada execução e só funciona com sua máquina + os
containers ligados) — ideal para demonstração. O app já confia em proxies
(`trustProxies`), então HTTPS e os links do painel funcionam corretamente atrás do
túnel. Para uma URL fixa, use um túnel nomeado do Cloudflare (com domínio) ou
hospede de verdade (ver abaixo).

## Produção

A configuração de produção é um **override do Compose** (`docker-compose.prod.yml`)
sobre o arquivo base — não muda código. Ele troca o worker para `queue:work`, aplica
`restart: always`, usa um `php.ini` de produção (OPcache sem revalidar timestamps) e
**não publica** as portas de MySQL/Redis no host.

```bash
cp .env.production.example .env     # ajuste senhas, APP_URL, APP_KEY
just up-prod                        # docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
just deploy                         # composer --no-dev, build de assets, migrate --force, caches, queue:restart
```

O `just deploy` é o fluxo a rodar **a cada nova versão** (puxa dependências, compila
assets, migra, faz cache de config/rotas/views, reinicia os workers e limpa o OPcache).

> Para uma imagem 100% autocontida (CI/registry/Kubernetes) e HTTPS via reverse proxy,
> dá pra evoluir com um `Dockerfile` de produção multi-stage — peça quando definir a
> hospedagem.
