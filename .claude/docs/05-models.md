# Models

**CRITICAL:** Models are THIN - business logic belongs in Actions, not Models.

## Core Principles

- **`$guarded = []`** - Never use `$fillable` (use `$guarded` instead)
- **SoftDeletes** - Always use `SoftDeletes` trait
- **Eager Loading** - Prevent N+1 with `->with()` and `->load()`
- **Type Safety** - Use `casts()` method for type casting
- **Relationships** - Use proper return type hints
- **Thin Models** - NO business logic, only data access and relationships

## Model Structure

### Complete Model Example

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Business extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->state}";
    }

    // Mutators
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucfirst($value);
    }

    // Casts
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'opened_at' => 'datetime',
        ];
    }
}
```

## Relationships

### One to Many (hasMany / belongsTo)

```php
<?php

// Business has many MenuItems
final class Business extends Model
{
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}

// MenuItem belongs to Business
final class MenuItem extends Model
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}

// Usage
$business = Business::with('menuItems')->find(1);
$items = $business->menuItems; // Collection of MenuItems

$item = MenuItem::with('business')->find(1);
$business = $item->business; // Business instance
```

### Many to Many (belongsToMany)

```php
<?php

// MenuItem belongs to many Categories
final class MenuItem extends Model
{
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps()
            ->withPivot('sort_order', 'is_featured');
    }
}

// Category belongs to many MenuItems
final class Category extends Model
{
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class)
            ->withTimestamps()
            ->withPivot('sort_order', 'is_featured');
    }
}

// Usage
$item = MenuItem::with('categories')->find(1);
foreach ($item->categories as $category) {
    echo $category->pivot->sort_order;
    echo $category->pivot->is_featured;
}

// Attach/Detach
$item->categories()->attach($categoryId, ['sort_order' => 1, 'is_featured' => true]);
$item->categories()->detach($categoryId);
$item->categories()->sync([1, 2, 3]); // Replace all
```

### Has Many Through

```php
<?php

// Tenant has many MenuItems through Businesses
final class Tenant extends Model
{
    public function menuItems(): HasManyThrough
    {
        return $this->hasManyThrough(MenuItem::class, Business::class);
    }
}

// Usage
$tenant = Tenant::with('menuItems')->find(1);
$items = $tenant->menuItems; // All menu items across all businesses
```

### Polymorphic Relationships

```php
<?php

// Image can belong to Business or MenuItem
final class Image extends Model
{
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}

final class Business extends Model
{
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}

final class MenuItem extends Model
{
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}

// Usage
$business = Business::with('images')->find(1);
$images = $business->images;

$image = Image::with('imageable')->find(1);
$owner = $image->imageable; // Business or MenuItem
```

## Eager Loading (Prevent N+1)

```php
<?php

// ❌ BAD - N+1 query problem
$businesses = Business::all();
foreach ($businesses as $business) {
    echo $business->tenant->name; // Query per business
}

// ✅ GOOD - Eager loading
$businesses = Business::with('tenant')->get();
foreach ($businesses as $business) {
    echo $business->tenant->name; // No extra queries
}

// ✅ GOOD - Nested eager loading
$businesses = Business::with(['tenant', 'locations', 'menuItems.categories'])->get();

// ✅ GOOD - Conditional eager loading
$businesses = Business::with(['menuItems' => function ($query) {
    $query->where('is_active', true)->orderBy('name');
}])->get();

// ✅ GOOD - Lazy eager loading (when you forgot)
$businesses = Business::all();
$businesses->load('tenant'); // Load relationship after fetch
```

## Scopes

### Local Scopes

```php
<?php

final class Business extends Model
{
    // Simple scope
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope with parameters
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Scope with multiple parameters
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Chainable scopes
    public function scopePopular($query)
    {
        return $query->where('views', '>=', 1000);
    }
}

// Usage
Business::active()->get();
Business::ofType('restaurant')->get();
Business::active()->ofType('restaurant')->popular()->get();
```

### Global Scopes

```php
<?php

// app/Models/Scopes/TenantScope.php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    }
}

// Apply to model
final class Business extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}

// Usage
Business::all(); // Automatically filtered by tenant_id

// Remove scope temporarily
Business::withoutGlobalScope(TenantScope::class)->get();
```

## Casts & Accessors/Mutators

### Type Casting

```php
<?php

final class Business extends Model
{
    protected function casts(): array
    {
        return [
            // Basic types
            'is_active' => 'boolean',
            'views' => 'integer',
            'rating' => 'float',

            // Dates
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',

            // JSON
            'settings' => 'array',
            'metadata' => 'array',

            // Encrypted
            'secret_key' => 'encrypted',

            // Collections
            'tags' => 'collection',

            // Enums
            'type' => BusinessTypeEnum::class,
            'status' => BusinessStatusEnum::class,
        ];
    }
}

// Usage
$business->is_active; // bool (not string "1" or "0")
$business->opened_at; // Carbon instance
$business->settings; // array (from JSON)
$business->type; // BusinessTypeEnum instance
```

### Accessors (Get)

```php
<?php

final class Business extends Model
{
    // Accessor - get computed value
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->postal_code}";
    }

    public function getFormattedPhoneAttribute(): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $this->phone);
    }
}

// Usage
$business->full_address; // "123 Main St, New York, NY 10001"
$business->formatted_phone; // "(555) 123-4567"
```

### Mutators (Set)

```php
<?php

final class Business extends Model
{
    // Mutator - transform before saving
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = preg_replace('/[^0-9]/', '', $value ?? '');
    }
}

// Usage
$business->name = 'restaurant'; // Stored as "Restaurant"
$business->phone = '(555) 123-4567'; // Stored as "5551234567"
```

## Model Observers

### Creating an Observer

```bash
php artisan make:observer BusinessObserver --model=Business
```

### Observer Pattern

```php
<?php

// app/Observers/BusinessObserver.php
namespace App\Observers;

use App\Events\BusinessCreated;
use App\Models\Business;
use Illuminate\Support\Str;

final class BusinessObserver
{
    public function creating(Business $business): void
    {
        // Before creating
        $business->slug = Str::slug($business->name);

        if (!$business->tenant_id) {
            $business->tenant_id = auth()->user()->tenant_id;
        }
    }

    public function created(Business $business): void
    {
        // After created
        event(new BusinessCreated($business));
    }

    public function updating(Business $business): void
    {
        // Before updating
        if ($business->isDirty('name')) {
            $business->slug = Str::slug($business->name);
        }
    }

    public function updated(Business $business): void
    {
        // After updated
    }

    public function deleting(Business $business): void
    {
        // Before deleting (soft or hard)
    }

    public function deleted(Business $business): void
    {
        // After deleted (soft or hard)
    }

    public function forceDeleted(Business $business): void
    {
        // After permanent deletion
        // Delete related images, etc.
    }

    public function restored(Business $business): void
    {
        // After restoring soft-deleted record
    }
}
```

### Register Observer

```php
<?php

// app/Providers/AppServiceProvider.php
use App\Models\Business;
use App\Observers\BusinessObserver;

public function boot(): void
{
    Business::observe(BusinessObserver::class);
}
```

## Factories

### Creating Factories

```bash
php artisan make:factory BusinessFactory
```

### Factory Pattern

```php
<?php

// database/factories/BusinessFactory.php
namespace Database\Factories;

use App\Enums\BusinessTypeEnum;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BusinessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(BusinessTypeEnum::cases())->value,
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'is_active' => true,
            'settings' => [
                'currency' => 'USD',
                'timezone' => 'America/New_York',
            ],
        ];
    }

    // State: inactive business
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // State: restaurant type
    public function restaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BusinessTypeEnum::RESTAURANT->value,
        ]);
    }

    // State: with specific tenant
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    // State: with locations
    public function withLocations(int $count = 3): static
    {
        return $this->has(Location::factory()->count($count), 'locations');
    }
}
```

### Using Factories

```php
<?php

// Create single record
$business = Business::factory()->create();

// Create multiple records
$businesses = Business::factory()->count(5)->create();

// Create with custom attributes
$business = Business::factory()->create([
    'name' => 'My Business',
    'is_active' => false,
]);

// Use states
$business = Business::factory()->inactive()->create();
$business = Business::factory()->restaurant()->create();

// Chain states
$business = Business::factory()
    ->restaurant()
    ->inactive()
    ->create();

// Create with relationships
$business = Business::factory()
    ->forTenant($tenant)
    ->withLocations(5)
    ->create();

// Make without saving
$business = Business::factory()->make();
```

## Multi-Tenancy Patterns

### Tenant Scoping

```php
<?php

// All tenant-scoped models
abstract class TenantScopedModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}

// Use in models
final class Business extends TenantScopedModel
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
}

// Usage
Business::all(); // Automatically filtered by current tenant
```

## Best Practices

### ✅ DO

- Use `$guarded = []` instead of `$fillable`
- Always use `SoftDeletes` trait
- Use `casts()` method for type casting
- Eager load relationships to prevent N+1
- Use type hints for relationships
- Use observers for side effects
- Use factories with states for testing
- Use scopes for reusable queries
- Keep models thin (no business logic)
- Use accessors/mutators for formatting
- Use global scopes for tenant isolation

### ❌ DON'T

- Don't use `$fillable` (use `$guarded` instead)
- Don't put business logic in models
- Don't forget eager loading
- Don't use `DB::` facade (use Eloquent)
- Don't forget to type-hint relationships
- Don't query in loops (N+1 problem)
- Don't use magic methods without casts
- Don't skip factories in tests
- Don't forget SoftDeletes trait
- Don't hardcode tenant_id everywhere

## Quick Reference Checklist

Before finalizing ANY model:

- [ ] `$guarded = []` (not `$fillable`)
- [ ] `SoftDeletes` trait added
- [ ] `HasFactory` trait added
- [ ] All relationships type-hinted
- [ ] `casts()` method defined
- [ ] Factory created with states
- [ ] Observer created (if needed)
- [ ] Scopes defined for common queries
- [ ] Tenant scoping applied (if needed)
- [ ] Eager loading used in queries
- [ ] No business logic in model
- [ ] Tests written using factories

## Common Patterns Summary

```php
<?php

// Model structure
final class Model extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    // Relationships (type-hinted)
    public function relation(): BelongsTo { }

    // Scopes (local)
    public function scopeActive($query) { }

    // Accessors (get)
    public function getFullNameAttribute(): string { }

    // Mutators (set)
    public function setNameAttribute(string $value): void { }

    // Casts
    protected function casts(): array { }
}

// Usage
Model::with('relation')         // Eager load
    ->active()                  // Scope
    ->where('foo', 'bar')       // Query
    ->get();                    // Execute

// Factory
Model::factory()
    ->state()
    ->count(5)
    ->create();
```
