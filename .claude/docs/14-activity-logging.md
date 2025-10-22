# Activity Logging (Filament Logger)

**Stack:** `unknow-sk/filament-logger` ^1.0 + `spatie/laravel-activitylog` ^4.10

## Core Principles

- **Automatic Logging** - Filament resource events are logged automatically
- **Manual Logging** - Use `LogActivity` trait for custom model logging
- **Extensible** - Create custom loggers for domain-specific events
- **Multi-Tenancy Safe** - Integrates with tenant scoping
- **User Attribution** - Automatically tracks which user performed the action
- **Searchable** - View and search activity logs in Filament admin panel

## What Gets Logged

By default, the following events are logged:

1. **Filament Resource Events** - Create, update, delete operations on Filament resources
2. **Login Events** - User authentication (login/logout)
3. **Notification Events** - When notifications are sent

## Installation (Already Done)

The package is already installed and configured in this project:

```bash
# Package installed via composer
composer require unknow-sk/filament-logger

# Installation command executed
php artisan filament-logger:install

# Translations published
php artisan vendor:publish --tag="filament-logger-translations"
```

## Configuration

### Published Files

```
config/
├── filament-logger.php         # Main configuration
└── activitylog.php             # Spatie activity log config

database/migrations/
├── *_create_activity_log_table.php
├── *_add_event_column_to_activity_log_table.php
└── *_add_batch_uuid_column_to_activity_log_table.php

lang/vendor/filament-logger/    # Translations (EN only by default)
```

### Running Migrations

```bash
php artisan migrate
```

### Register in Panel Provider

Add the Activity resource to your Filament panel:

```php
<?php

// app/Providers/Filament/AdminPanelProvider.php

use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->resources([
            config('filament-logger.activity_resource'),
            // ... other resources
        ]);
}
```

## Using Activity Logging in Models

### Automatic Logging for Filament Resources

Filament resources are logged automatically when using CRUD operations. No additional setup required.

### Manual Logging for Custom Models

For models that are NOT Filament resources, use the `LogsActivity` trait:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Business extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [];

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'email', 'phone', 'is_active']) // Fields to log
            ->logOnlyDirty() // Only log changed attributes
            ->dontSubmitEmptyLogs() // Skip logging if nothing changed
            ->useLogName('business') // Custom log name
            ->setDescriptionForEvent(fn(string $eventName) => "Business {$eventName}");
    }
}
```

### LogOptions Configuration

| Method                      | Description                                      |
|-----------------------------|--------------------------------------------------|
| `logOnly(['field'])`        | Log only specific attributes                     |
| `logAll()`                  | Log all attributes (⚠️ can be verbose)           |
| `logOnlyDirty()`            | Log only changed attributes                      |
| `dontLogIfAttributesChangedOnly(['field'])` | Skip logging if only certain fields changed |
| `dontSubmitEmptyLogs()`     | Skip logging if nothing changed                  |
| `useLogName('name')`        | Set custom log name (default: 'default')         |
| `setDescriptionForEvent(fn)` | Custom description for events                   |

## Manual Activity Logging

### Simple Log

```php
use Spatie\Activitylog\Models\Activity;

// Log an activity
activity()
    ->log('Order placed by customer');

// Log with custom properties
activity()
    ->performedOn($order)
    ->causedBy($user)
    ->withProperties(['total' => 150.00, 'items' => 3])
    ->log('Order placed');

// Log without user (system event)
activity()
    ->performedOn($business)
    ->withProperty('reason', 'Automated cleanup')
    ->log('Business archived');
```

### Event-Based Logging

```php
<?php

// In an Action
namespace App\Actions\Business;

use App\Models\Business;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateBusinessAction
{
    use AsAction;

    public function handle(Tenant $tenant, CreateBusinessData $data): Business
    {
        $business = $tenant->businesses()->create($data->toArray());

        // Manual activity log
        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->withProperties([
                'tenant_id' => $tenant->id,
                'plan' => $tenant->plan->name,
            ])
            ->log('Business created via action');

        return $business;
    }
}
```

### Log Custom Events

```php
<?php

// In an Observer
namespace App\Observers;

use App\Models\Business;

final class BusinessObserver
{
    public function created(Business $business): void
    {
        activity()
            ->performedOn($business)
            ->withProperties([
                'business_type' => $business->type->value,
                'is_active' => $business->is_active,
            ])
            ->log('Business created');
    }

    public function updated(Business $business): void
    {
        if ($business->isDirty('is_active')) {
            $status = $business->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($business)
                ->withProperty('previous_status', $business->getOriginal('is_active'))
                ->log("Business {$status}");
        }
    }

    public function deleted(Business $business): void
    {
        activity()
            ->performedOn($business)
            ->log('Business soft deleted');
    }
}
```

## Retrieving Activity Logs

### Get All Activity for a Model

```php
// Get all activity for a business
$activities = Activity::forSubject($business)->get();

// Get recent activity
$recentActivity = Activity::forSubject($business)
    ->latest()
    ->take(10)
    ->get();
```

### Get Activity by User

```php
// Get all activity by a user
$userActivities = Activity::causedBy($user)->get();

// Get user activity for specific model
$businessActivities = Activity::causedBy($user)
    ->forSubject($business)
    ->get();
```

### Get Activity by Log Name

```php
// Get business-specific logs
$businessLogs = Activity::inLog('business')->get();

// Get default logs
$defaultLogs = Activity::inLog('default')->get();
```

### Advanced Queries

```php
// Get activity within date range
$activities = Activity::whereBetween('created_at', [$startDate, $endDate])
    ->get();

// Get activity with properties
$activities = Activity::where('properties->total', '>', 100)
    ->get();

// Get activity by description
$activities = Activity::where('description', 'like', '%created%')
    ->get();
```

## Viewing Activity in Filament

### Activity Resource

The Activity resource is automatically registered when you add it to your panel provider.

**Features:**
- **Table View** - List all activities with filtering and searching
- **Detail View** - View full activity details including properties
- **Search** - Search by description, causer, subject
- **Filters** - Filter by date, log name, causer

### Customizing the Activity Resource

If you need to customize the Activity resource, publish the configuration:

```bash
php artisan vendor:publish --tag="filament-logger-config"
```

Then modify `config/filament-logger.php`:

```php
<?php

return [
    'activity_resource' => \UnknowSK\FilamentLogger\Resources\ActivityResource::class,

    'resources' => [
        'label' => 'Activity',
        'plural_label' => 'Activities',
        'navigation_group' => 'System',
        'navigation_icon' => 'heroicon-o-shield-check',
        'navigation_sort' => null,
    ],

    'datetime_format' => 'd/m/Y H:i:s',
];
```

## Authorization

### Using Policies

Create a policy for the Activity model:

```bash
php artisan make:policy ActivityPolicy --model=Activity
```

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

final class ActivityPolicy
{
    /**
     * Determine if user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        // Only super admins can view activity logs
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if user can view the activity.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Users cannot create activities manually through UI.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Users cannot update activities.
     */
    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    /**
     * Only super admins can delete activities.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Only super admins can restore activities.
     */
    public function restore(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Only super admins can permanently delete activities.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

Register the policy in `AuthServiceProvider`:

```php
<?php

use Spatie\Activitylog\Models\Activity;
use App\Policies\ActivityPolicy;

protected $policies = [
    Activity::class => ActivityPolicy::class,
];
```

**Note:** If using Filament Shield, the policy will be auto-generated.

## Multi-Tenancy Support

### Scoping Activity to Tenant

If you need to scope activity logs to tenants, modify the Activity resource:

```php
<?php

// Create a custom Activity Resource
namespace App\Filament\Resources;

use UnknowSK\FilamentLogger\Resources\ActivityResource as BaseActivityResource;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

final class ActivityResource extends BaseActivityResource
{
    /**
     * Scope activity to current tenant.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                // Filter by authenticated user's tenant
                $tenantId = auth()->user()->tenant_id;

                $query->whereHasMorph('subject', '*', function ($q) use ($tenantId) {
                    if (Schema::hasColumn($q->getModel()->getTable(), 'tenant_id')) {
                        $q->where('tenant_id', $tenantId);
                    }
                });
            });
    }
}
```

Then update `config/filament-logger.php`:

```php
'activity_resource' => \App\Filament\Resources\ActivityResource::class,
```

## Translations

### Adding Spanish and Portuguese Translations

Create translation files for ES and PT-BR:

```bash
mkdir -p lang/vendor/filament-logger/es
mkdir -p lang/vendor/filament-logger/pt_BR
```

**Spanish (lang/vendor/filament-logger/es/filament-logger.json):**

```json
{
    "Activity": "Actividad",
    "Activities": "Actividades",
    "Description": "Descripción",
    "Subject Type": "Tipo de Sujeto",
    "Subject": "Sujeto",
    "Causer": "Causante",
    "Properties": "Propiedades",
    "Created at": "Creado el",
    "Old": "Antiguo",
    "Attributes": "Atributos"
}
```

**Portuguese BR (lang/vendor/filament-logger/pt_BR/filament-logger.json):**

```json
{
    "Activity": "Atividade",
    "Activities": "Atividades",
    "Description": "Descrição",
    "Subject Type": "Tipo de Assunto",
    "Subject": "Assunto",
    "Causer": "Causador",
    "Properties": "Propriedades",
    "Created at": "Criado em",
    "Old": "Antigo",
    "Attributes": "Atributos"
}
```

## Testing Activity Logging

### Testing Manual Logs

```php
<?php

use Spatie\Activitylog\Models\Activity;

it('logs business creation', function () {
    $user = User::factory()->create();
    actingAs($user);

    $business = Business::factory()->create();

    // Assert activity was logged
    expect(Activity::forSubject($business)->count())->toBe(1);

    $activity = Activity::forSubject($business)->first();

    expect($activity->description)->toBe('created')
        ->and($activity->causer_id)->toBe($user->id);
});

it('logs only changed attributes', function () {
    $user = User::factory()->create();
    actingAs($user);

    $business = Business::factory()->create(['name' => 'Original Name']);

    // Update only the name
    $business->update(['name' => 'New Name']);

    $activity = Activity::forSubject($business)
        ->where('description', 'updated')
        ->first();

    expect($activity->properties->get('attributes'))->toHaveKey('name', 'New Name')
        ->and($activity->properties->get('old'))->toHaveKey('name', 'Original Name');
});

it('associates activity with authenticated user', function () {
    $user = User::factory()->create();
    actingAs($user);

    $business = Business::factory()->create();

    $activity = Activity::forSubject($business)->first();

    expect($activity->causer)->toBeInstanceOf(User::class)
        ->and($activity->causer->id)->toBe($user->id);
});

it('logs custom properties', function () {
    $user = User::factory()->create();
    actingAs($user);

    $order = Order::factory()->create();

    activity()
        ->performedOn($order)
        ->withProperties(['total' => 150.00, 'items' => 3])
        ->log('Order placed');

    $activity = Activity::forSubject($order)->first();

    expect($activity->properties->get('total'))->toBe(150.00)
        ->and($activity->properties->get('items'))->toBe(3);
});
```

### Testing Activity Resource Access

```php
<?php

use App\Filament\Resources\ActivityResource;
use function Pest\Livewire\livewire;

it('super admin can view activities', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    actingAs($admin);

    livewire(ActivityResource\Pages\ListActivities::class)
        ->assertSuccessful();
});

it('regular user cannot view activities', function () {
    $user = User::factory()->create();
    actingAs($user);

    livewire(ActivityResource\Pages\ListActivities::class)
        ->assertForbidden();
});

it('can search activities by description', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    actingAs($admin);

    activity()->log('Business created');
    activity()->log('Order placed');

    livewire(ActivityResource\Pages\ListActivities::class)
        ->searchTable('Business created')
        ->assertCanSeeTableRecords(
            Activity::where('description', 'Business created')->get()
        );
});
```

## Privacy & PII Best Practices

### Avoid Logging Sensitive Personal Information (PII)

**CRITICAL:** Be extremely careful about what you log. Logging PII can violate privacy laws (GDPR, CCPA, etc.) and create security risks.

### ❌ DON'T Log PII

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class User extends Authenticatable
{
    use LogsActivity;

    // ❌ WRONG - Logging email exposes PII
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'email_verified_at']) // Don't log 'email'
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### ✅ DO - Exclude Sensitive Fields

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class User extends Authenticatable
{
    use LogsActivity;

    // ✅ CORRECT - Only log safe, non-PII fields
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email_verified_at']) // Removed 'email'
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user')
            ->setDescriptionForEvent(fn (string $eventName): string => "User {$eventName}");
    }
}
```

### Sensitive Fields to Avoid Logging

| Field Type          | Examples                                      | Why                               |
|---------------------|-----------------------------------------------|-----------------------------------|
| **Email Addresses** | `email`, `secondary_email`                    | Personal identifier, GDPR concern |
| **Phone Numbers**   | `phone`, `mobile`, `emergency_contact`        | Personal identifier               |
| **Passwords**       | `password`, `password_hash`                   | Security risk                     |
| **Tokens**          | `api_token`, `remember_token`, `reset_token`  | Security risk                     |
| **SSN/Tax IDs**     | `ssn`, `tax_id`, `national_id`                | Highly sensitive PII              |
| **Credit Cards**    | `card_number`, `cvv`, `expiry`                | PCI compliance                    |
| **Addresses**       | `street_address`, `postal_code` (sometimes)   | Personal identifier               |
| **IP Addresses**    | `ip_address`, `last_login_ip`                 | Personal identifier (GDPR)        |
| **Biometric Data**  | `fingerprint`, `face_id`                      | Highly sensitive PII              |

### Safe Fields to Log

✅ Non-identifying metadata: `is_active`, `is_verified`, `status` \
✅ Timestamps: `created_at`, `updated_at`, `verified_at` \
✅ Counts/metrics: `login_count`, `orders_count` \
✅ Preferences: `theme`, `language`, `timezone` \
✅ Display names: `name`, `username` (if not unique identifier)

### Alternative Approaches

If you need to track changes to sensitive fields without logging the actual values:

```php
<?php

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'email_verified_at'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->dontLogIfAttributesChangedOnly(['email', 'phone']) // Track that it changed, but don't log values
        ->useLogName('user')
        ->setDescriptionForEvent(fn (string $eventName): string => "User {$eventName}");
}
```

Or log a flag indicating the change:

```php
<?php

// In Observer
public function updated(User $user): void
{
    if ($user->isDirty('email')) {
        activity()
            ->performedOn($user)
            ->withProperty('email_changed', true) // Flag only, not the actual email
            ->log('User email updated');
    }
}
```

## Best Practices

### ✅ DO

- Use `LogsActivity` trait for models that need automatic logging
- Configure `logOnly()` to avoid logging sensitive data
- **Exclude PII (email, phone, addresses, SSN, etc.) from logs**
- Use `logOnlyDirty()` to reduce log volume
- Use custom log names (`useLogName()`) to organize logs
- Add contextual properties with `withProperties()`
- Test that important activities are being logged
- Scope Activity resource to tenants for multi-tenancy
- Use policies to restrict who can view/delete activity logs
- Create translated messages for all supported languages
- **Review data privacy laws (GDPR, CCPA) before logging user data**

### ❌ DON'T

- Don't log sensitive data (passwords, tokens, credit cards)
- **Don't log PII (email, phone, addresses, SSN, IP addresses)**
- Don't use `logAll()` on models with many fields
- Don't forget to configure `logOnly()` fields
- Don't allow regular users to delete activity logs
- Don't skip testing activity logging
- Don't log every single attribute change (be selective)
- Don't forget to add indexes to `activity_log` table for performance
- Don't expose activity logs without proper authorization
- **Don't log data that could violate privacy regulations**

## Performance Optimization

### Database Indexes

The default migrations include essential indexes, but for high-volume logging, consider adding:

```php
// In a new migration
Schema::table('activity_log', function (Blueprint $table) {
    $table->index(['causer_id', 'causer_type']);
    $table->index(['subject_id', 'subject_type']);
    $table->index('created_at');
    $table->index('log_name');
});
```

### Pruning Old Logs

Set up automatic pruning of old activity logs:

```php
<?php

// In app/Console/Kernel.php or routes/console.php
use Spatie\Activitylog\Models\Activity;

Schedule::command('model:prune', ['--model' => Activity::class])
    ->daily();
```

Then add `Prunable` trait to Activity model:

```php
<?php

// Create a custom Activity model if needed
namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Spatie\Activitylog\Models\Activity as BaseActivity;

final class Activity extends BaseActivity
{
    use MassPrunable;

    /**
     * Prune activity logs older than 90 days.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
```

## Quick Reference Checklist

Before implementing activity logging:

- [ ] `LogsActivity` trait added to model
- [ ] `getActivitylogOptions()` configured
- [ ] `logOnly()` or `logAll()` specified
- [ ] `logOnlyDirty()` enabled (recommended)
- [ ] Custom log name set (optional)
- [ ] Sensitive fields excluded from logging
- [ ] Activity resource registered in panel
- [ ] Policy created and registered
- [ ] Translations added (EN, ES, PT-BR)
- [ ] Tests written for activity logging
- [ ] Multi-tenancy scoping implemented (if needed)
- [ ] Performance indexes added (for high-volume)

## Common Patterns Summary

```php
// ✅ Model with LogsActivity
use Spatie\Activitylog\Traits\LogsActivity;

final class Business extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

// ✅ Manual activity log
activity()
    ->performedOn($model)
    ->causedBy($user)
    ->withProperties(['key' => 'value'])
    ->log('Description');

// ✅ Get activity for model
$activities = Activity::forSubject($business)->get();

// ✅ Get activity by user
$activities = Activity::causedBy($user)->get();

// ✅ Test activity logging
it('logs model creation', function () {
    $model = Model::factory()->create();
    expect(Activity::forSubject($model)->count())->toBe(1);
});
```
