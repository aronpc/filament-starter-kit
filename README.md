# Filament Starter Kit

Um starter kit completo com Filament 4.1, incluindo sistema de permissões e gerenciamento de mídia.

## Stack

- **Laravel 12** (PHP 8.4+)
- **Filament 4.1** - Admin Panel completo
- **Filament Shield** - Roles & Permissions (Spatie Permission)
- **Filament Spatie Media Library** - Gerenciamento de mídia
- **Filament Logger** - Activity logging (Spatie Activity Log)
- **Livewire 3** - Full-stack framework
- **Tailwind CSS 4** - Styling
- **Pest 4** - Testing framework
- **PHPStan, Rector, Pint** - Code quality
- **Laravel Actions** - Business logic pattern
- **ArchTech Enums** - Enhanced PHP enums

## Instalação

```bash
git clone git@github.com:aronpc/filament-starter-kit.git
cd filament-starter-kit

composer install
npm install

cp .env.example .env
php artisan key:generate

# Criar banco de dados e rodar migrations
touch database/database.sqlite
php artisan migrate

# Instalar Shield (Roles & Permissions) - Filament 4
php artisan shield:install --fresh

# Criar super admin
php artisan shield:super-admin

# Build frontend assets
npm run build

# Iniciar servidor
composer run dev
```

## Acesso ao Painel

Após criar o super admin, acesse:
- URL: `http://localhost:8000/admin`
- Login com as credenciais do super admin criado

## Scripts Disponíveis

```bash
# Desenvolvimento
composer run dev              # Inicia servidor, queue, logs e vite

# Testes e Qualidade
composer test                 # Roda testes, PHPStan e Rector check
composer fix                  # Corrige código com Rector e Pint

# Frontend
npm run dev                   # Dev server com HMR
npm run build                 # Build de produção

# Filament
php artisan filament:user               # Criar usuário
php artisan shield:super-admin          # Criar super admin
php artisan shield:generate --all      # Gerar permissões para recursos

# Documentação
php artisan sync:ai-guidelines          # Sincronizar documentos guia do repositório público
php artisan sync:ai-guidelines --force  # Forçar sincronização sobrescrevendo arquivos existentes
```

## Estrutura

```
├── app/
│   ├── Filament/
│   │   ├── Resources/      # Recursos do Filament
│   │   └── Pages/          # Páginas customizadas
│   ├── Models/             # Eloquent models
│   └── Providers/
│       └── Filament/
│           └── AdminPanelProvider.php
├── database/
│   └── migrations/         # Inclui permissões e media
├── resources/
│   ├── css/app.css         # Tailwind CSS
│   └── views/
└── tests/
```

## Features

### Admin Panel (Filament 4.1)
- ✅ Dashboard com widgets
- ✅ Gerenciamento de usuários
- ✅ Sistema completo de CRUD
- ✅ Formulários avançados com Schemas
- ✅ Tabelas com filtros deferred e ações
- ✅ Notificações
- ✅ Multi-tenancy ready
- ✅ Actions encapsuladas (modais, formulários, lógica)
- ✅ Infolists para dados read-only

### Shield (Permissions)
- ✅ Roles e Permissions (Spatie)
- ✅ Super Admin role
- ✅ Policy generation automática
- ✅ Resource permissions
- ✅ Custom permissions

### Media Library
- ✅ Upload de arquivos
- ✅ Conversões de imagem automáticas
- ✅ Múltiplas collections
- ✅ Integração com Filament Forms

### Activity Logging
- ✅ Log automático de ações em Filament Resources
- ✅ Log de login/logout de usuários
- ✅ Log de notificações enviadas
- ✅ Log customizado em models (trait LogsActivity)
- ✅ Visualização de logs no Admin Panel
- ✅ Agrupado em "User Management"

### AI Guidelines Sync
- ✅ Sincronização automática de documentos guia do repositório público
- ✅ Download de arquivos .md do GitHub API
- ✅ Atualização automática do CLAUDE.md com referências
- ✅ Modo force para sobrescrever arquivos existentes
- ✅ Organização alfabética dos documentos

### Code Quality & Architecture
- ✅ Architecture tests com Pest
- ✅ PHPStan nivel máximo (type safety)
- ✅ Rector para refactoring automático
- ✅ Laravel Pint para formatação (PSR-12)
- ✅ Laravel Actions (business logic pattern)
- ✅ ArchTech Enums (type-safe enums)
- ✅ Value Objects (DTOs) com readonly properties
- ✅ Strict types em todos os arquivos

## Criando Recursos

```bash
# Criar um Resource completo
php artisan make:filament-resource Post --generate

# Gerar permissões para o resource
php artisan shield:generate --resource=PostResource

# Criar página customizada
php artisan make:filament-page Settings

# Criar widget
php artisan make:filament-widget StatsOverview
```

## Custom Theme

Para customizar o tema do Filament:

```bash
# Publicar views do Filament
php artisan filament:assets

# Criar theme customizado
php artisan make:filament-theme
```

Depois configure no `AdminPanelProvider`:

```php
->theme(asset('css/filament/admin/theme.css'))
```

## Permissões

O Shield gera automaticamente permissões para seus resources:
- `view_any_{resource}`
- `view_{resource}`
- `create_{resource}`
- `update_{resource}`
- `delete_{resource}`
- `delete_any_{resource}`

Para regenerar permissões:
```bash
php artisan shield:generate --all
```

## Laravel Actions

Use Actions para business logic (não Services):

```bash
php artisan make:action CreatePostAction
```

```php
use Lorisleiva\Actions\Concerns\AsAction;

final class CreatePostAction
{
    use AsAction;

    public function handle(CreatePostData $data): Post
    {
        return Post::create($data->toArray());
    }
}

// Uso
CreatePostAction::run($data);
```

## ArchTech Enums

Enums type-safe com recursos avançados:

```php
use App\Contracts\HasEnumFeatures;

enum StatusEnum: string
{
    use HasEnumFeatures;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enums.status.active'),
            self::Inactive => __('enums.status.inactive'),
        };
    }
}

// Uso com comparações type-safe
if ($status->is(StatusEnum::Active)) { }
if ($status->in([StatusEnum::Active, StatusEnum::Inactive])) { }
```

## Media Library

Para usar a Media Library em seus models:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public');
    }
}
```

No Filament Form:

```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('image')
    ->collection('images')
    ->multiple()
    ->image()
```

## Activity Logging

O Activity Logging está configurado e pronto para uso. Para adicionar logging a um model:

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Post extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content', 'is_published'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('post')
            ->setDescriptionForEvent(fn (string $eventName): string => "Post {$eventName}");
    }
}
```

### Visualizar Logs

Acesse `/admin` e navegue até **User Management > Activities** para visualizar todos os logs de atividade.

### Log Manual

```php
// Log simples
activity()->log('Ação customizada realizada');

// Log com contexto
activity()
    ->performedOn($model)
    ->causedBy($user)
    ->withProperties(['key' => 'value'])
    ->log('Descrição da ação');
```

Para mais detalhes, consulte `.claude/docs/14-activity-logging.md`.

## AI Guidelines Sync

O projeto inclui um comando para sincronizar automaticamente documentos guia do repositório público `filament-core-starter-kit`:

```bash
# Sincronizar documentos (apenas novos)
php artisan sync:ai-guidelines

# Forçar sincronização completa (sobrescreve arquivos existentes)
php artisan sync:ai-guidelines --force
```

### Funcionalidades

- **Download Automático**: Baixa arquivos `.md` do diretório `.claude/docs` do repositório público
- **Atualização do CLAUDE.md**: Atualiza automaticamente as referências entre as tags `<filament-core-startkit-guidelines>`
- **Modo Force**: Opção `--force` para sobrescrever arquivos existentes
- **Organização**: Mantém os documentos em ordem alfabética
- **Verificação**: Verifica a existência de arquivos antes de baixar

### Estrutura

Os documentos são baixados para:
```
.claude/docs/
├── 01-project-structure.md
├── 02-coding-standards.md
├── 03-architecture-patterns.md
└── ...
```

O comando atualiza o arquivo `CLAUDE.md` incluindo as referências aos documentos na forma:
```markdown
@.claude/docs/01-project-structure.md
@.claude/docs/02-coding-standards.md
```

## Testing

```bash
# Rodar todos os testes
composer test

# Testes específicos
./vendor/bin/pest tests/Feature/Filament/

# Com coverage
composer test:unit:coverage
```

## Deploy

Para deploy em produção:

```bash
# Otimizar aplicação
php artisan optimize
php artisan filament:optimize

# Cache de rotas e config
php artisan route:cache
php artisan config:cache
php artisan view:cache

# Build assets
npm run build
```

## Licença

MIT

## Recursos

- [Filament v4 Docs](https://filamentphp.com/docs/4.x)
- [Filament Shield Docs](https://github.com/bezhanSalleh/filament-shield)
- [Filament Logger Docs](https://github.com/unknow-sk/filament-logger)
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Laravel Actions](https://laravelactions.com/)
- [ArchTech Enums](https://github.com/archtechx/enums)
