# Testing (Pest PHP)

**CRITICAL:** All code MUST be tested. No exceptions.

## Core Principles

- **100% Coverage** - All code paths must be tested (happy, failure, edge cases)
- **Feature Tests** - Test HTTP endpoints, Actions, business workflows
- **Unit Tests** - Test individual classes, DTOs, calculations, Enums
- **Factories** - Create test data using factories with states
- **Isolation** - Tests must be independent and order-agnostic
- **Fast** - Keep tests fast (use `RefreshDatabase`, avoid external calls)

## Test Organization

```
tests/
├── Feature/
│   ├── Actions/            # Test Action classes
│   │   └── Business/
│   │       ├── CreateBusinessActionTest.php
│   │       └── UpdateBusinessActionTest.php
│   ├── Auth/               # Authentication flows
│   │   ├── LoginTest.php
│   │   └── RegistrationTest.php
│   ├── Http/               # Controllers & routes
│   │   └── Owner/
│   │       └── BusinessControllerTest.php
│   └── Policies/           # Authorization
│       └── BusinessPolicyTest.php
├── Unit/
│   ├── DataObjects/        # DTOs validation
│   │   └── CreateBusinessDataTest.php
│   ├── Enums/              # Enum methods
│   │   └── BusinessTypeEnumTest.php
│   └── Models/             # Model relationships & scopes
│       └── BusinessTest.php
└── Pest.php                # Global test configuration
```

## Naming Conventions

- **Test files:** `NameTest.php` (e.g., `BusinessTest.php`, `CreateBusinessActionTest.php`)
- **Test methods:** Use `it()` or `test()` with descriptive names
- **Factories:** `NameFactory.php` with states for different scenarios

## Feature Tests

### Testing HTTP Endpoints (Controllers)

```php
<?php

use App\Models\Business;
use App\Models\User;
use function Pest\Laravel\{actingAs, get, post, put, delete};

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('displays business index page', function () {
    $businesses = Business::factory()->count(3)->create([
        'tenant_id' => $this->user->tenant_id,
    ]);

    get(route('owner.businesses.index'))
        ->assertSuccessful()
        ->assertSee($businesses->first()->name)
        ->assertSee($businesses->last()->name);
});

it('displays business create form', function () {
    get(route('owner.businesses.create'))
        ->assertSuccessful()
        ->assertSee(__('fields.name'))
        ->assertSee(__('fields.email'));
});

it('creates a new business', function () {
    $data = [
        'name' => 'Test Business',
        'type' => 'restaurant',
        'email' => 'test@example.com',
    ];

    post(route('owner.businesses.store'), $data)
        ->assertRedirect(route('owner.businesses.index'))
        ->assertSessionHas('success');

    expect(Business::where('name', 'Test Business')->exists())->toBeTrue();
});

it('validates required fields when creating business', function () {
    post(route('owner.businesses.store'), [])
        ->assertSessionHasErrors(['name', 'type'])
        ->assertRedirect();
});

it('updates an existing business', function () {
    $business = Business::factory()->create(['tenant_id' => $this->user->tenant_id]);

    put(route('owner.businesses.update', $business), [
        'name' => 'Updated Name',
        'type' => $business->type,
    ])
        ->assertRedirect(route('owner.businesses.index'))
        ->assertSessionHas('success');

    expect($business->fresh()->name)->toBe('Updated Name');
});

it('deletes a business', function () {
    $business = Business::factory()->create(['tenant_id' => $this->user->tenant_id]);

    delete(route('owner.businesses.destroy', $business))
        ->assertRedirect(route('owner.businesses.index'))
        ->assertSessionHas('success');

    expect(Business::find($business->id))->toBeNull();
});
```

### Testing Actions

```php
<?php

use App\Actions\Business\CreateBusinessAction;
use App\DataObjects\Business\CreateBusinessData;
use App\Events\BusinessCreated;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

it('creates a business successfully', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();
    $data = CreateBusinessData::fromArray([
        'name' => 'Test Business',
        'type' => 'restaurant',
        'email' => 'test@example.com',
    ]);

    $business = CreateBusinessAction::run($tenant, $data);

    expect($business)->toBeInstanceOf(Business::class)
        ->and($business->name)->toBe('Test Business')
        ->and($business->type)->toBe('restaurant')
        ->and($business->tenant_id)->toBe($tenant->id);

    Event::assertDispatched(BusinessCreated::class);
});

it('throws exception when business limit exceeded', function () {
    $tenant = Tenant::factory()->create();
    $tenant->plan->update(['limits' => ['businesses' => 1]]);

    Business::factory()->create(['tenant_id' => $tenant->id]);

    $data = CreateBusinessData::fromArray([
        'name' => 'Second Business',
        'type' => 'restaurant',
    ]);

    expect(fn() => CreateBusinessAction::run($tenant, $data))
        ->toThrow(BusinessLimitExceededException::class);
});
```

### Testing Policies

```php
<?php

use App\Models\{Business, User};
use App\Policies\BusinessPolicy;

it('allows owner to view their own business', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['tenant_id' => $user->tenant_id]);

    $policy = new BusinessPolicy();

    expect($policy->view($user, $business))->toBeTrue();
});

it('denies viewing business from different tenant', function () {
    $user = User::factory()->create();
    $otherBusiness = Business::factory()->create(); // Different tenant

    $policy = new BusinessPolicy();

    expect($policy->view($user, $otherBusiness))->toBeFalse();
});

it('allows creating business if within limit', function () {
    $user = User::factory()->create();
    $user->tenant->plan->update(['limits' => ['businesses' => 5]]);

    Business::factory()->count(2)->create(['tenant_id' => $user->tenant_id]);

    $policy = new BusinessPolicy();

    expect($policy->create($user))->toBeTrue();
});

it('denies creating business if limit exceeded', function () {
    $user = User::factory()->create();
    $user->tenant->plan->update(['limits' => ['businesses' => 2]]);

    Business::factory()->count(2)->create(['tenant_id' => $user->tenant_id]);

    $policy = new BusinessPolicy();

    expect($policy->create($user))->toBeFalse();
});
```

## Unit Tests

### Testing Models

```php
<?php

use App\Models\{Business, MenuItem, Tenant};

it('has tenant relationship', function () {
    $business = Business::factory()->create();

    expect($business->tenant)->toBeInstanceOf(Tenant::class);
});

it('has many menu items', function () {
    $business = Business::factory()->create();
    MenuItem::factory()->count(3)->create(['business_id' => $business->id]);

    $business->load('menuItems');

    expect($business->menuItems)->toHaveCount(3)
        ->and($business->menuItems->first())->toBeInstanceOf(MenuItem::class);
});

it('scopes to active businesses', function () {
    Business::factory()->count(3)->create(['is_active' => true]);
    Business::factory()->count(2)->create(['is_active' => false]);

    $activeBusinesses = Business::active()->get();

    expect($activeBusinesses)->toHaveCount(3)
        ->and($activeBusinesses->every(fn ($b) => $b->is_active))->toBeTrue();
});

it('casts settings to array', function () {
    $business = Business::factory()->create([
        'settings' => ['currency' => 'USD', 'timezone' => 'America/New_York'],
    ]);

    expect($business->settings)->toBeArray()
        ->and($business->settings['currency'])->toBe('USD');
});
```

### Testing Data Objects (DTOs)

```php
<?php

use App\DataObjects\Business\CreateBusinessData;

it('creates DTO from array', function () {
    $data = CreateBusinessData::fromArray([
        'name' => 'Test Business',
        'type' => 'restaurant',
        'email' => 'test@example.com',
    ]);

    expect($data->name)->toBe('Test Business')
        ->and($data->type)->toBe('restaurant')
        ->and($data->email)->toBe('test@example.com');
});

it('converts DTO to array', function () {
    $data = new CreateBusinessData(
        name: 'Test Business',
        type: 'restaurant',
        email: 'test@example.com',
    );

    $array = $data->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('Test Business')
        ->and($array['type'])->toBe('restaurant');
});
```

### Testing Enums

```php
<?php

use App\Enums\BusinessTypeEnum;

it('returns correct label for restaurant', function () {
    app()->setLocale('en');
    expect(BusinessTypeEnum::RESTAURANT->label())->toBe('Restaurant');

    app()->setLocale('es');
    expect(BusinessTypeEnum::RESTAURANT->label())->toBe('Restaurante');
});

it('returns correct color for each type', function () {
    expect(BusinessTypeEnum::RESTAURANT->color())->toBe('success')
        ->and(BusinessTypeEnum::CAFE->color())->toBe('warning');
});

it('converts to select array', function () {
    $array = BusinessTypeEnum::toSelectArray();

    expect($array)->toBeArray()
        ->and($array['restaurant'])->toBe('Restaurant');
});
```

## Pest Features

### Using Datasets

```php
<?php

// Dataset for testing multiple scenarios
it('validates email format', function (string $email, bool $shouldPass) {
    $response = post('/businesses', [
        'name' => 'Test',
        'type' => 'restaurant',
        'email' => $email,
    ]);

    if ($shouldPass) {
        $response->assertRedirect();
    } else {
        $response->assertSessionHasErrors('email');
    }
})->with([
    'valid email' => ['test@example.com', true],
    'invalid format' => ['invalid-email', false],
    'missing @' => ['testexample.com', false],
    'empty' => ['', false],
]);

// Inline dataset
it('accepts valid business types', function (string $type) {
    post('/businesses', [
        'name' => 'Test',
        'type' => $type,
    ])->assertRedirect();
})->with(['restaurant', 'cafe', 'bar']);
```

### Mocking & Faking

```php
<?php

use Illuminate\Support\Facades\{Event, Mail, Notification, Queue, Storage};
use App\Events\BusinessCreated;
use App\Notifications\WelcomeNotification;

// Fake events
it('dispatches business created event', function () {
    Event::fake([BusinessCreated::class]);

    $business = Business::factory()->create();

    Event::assertDispatched(BusinessCreated::class, function ($event) use ($business) {
        return $event->business->id === $business->id;
    });
});

// Fake mail
it('sends welcome email', function () {
    Mail::fake();

    // Trigger email sending

    Mail::assertSent(WelcomeEmail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});

// Fake notifications
it('sends welcome notification', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->notify(new WelcomeNotification());

    Notification::assertSentTo($user, WelcomeNotification::class);
});

// Fake queue
it('dispatches job to queue', function () {
    Queue::fake();

    dispatch(new ProcessOrder($order));

    Queue::assertPushed(ProcessOrder::class);
});

// Fake storage
it('uploads file', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg');

    post('/upload', ['photo' => $file]);

    Storage::disk('public')->assertExists('photos/photo.jpg');
});
```

### Partial Mocking

```php
<?php

use App\Services\PaymentService;

it('calls payment service', function () {
    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('charge')
        ->once()
        ->with(100, 'USD')
        ->andReturn(true);

    $this->app->instance(PaymentService::class, $mock);

    // Call code that uses PaymentService
});
```

## Testing Helpers

### Common Pest/Laravel Helpers

```php
<?php

use function Pest\Laravel\{
    actingAs,        // Authenticate as user
    get,             // GET request
    post,            // POST request
    put,             // PUT request
    patch,           // PATCH request
    delete,          // DELETE request
    assertDatabaseHas, // Assert DB record exists
    assertDatabaseMissing, // Assert DB record missing
    assertDatabaseCount, // Assert DB table count
};

// Authentication
actingAs($user);
actingAs($user, 'admin'); // With guard

// HTTP requests
get('/dashboard');
post('/businesses', $data);
put("/businesses/{$business->id}", $data);
delete("/businesses/{$business->id}");

// Database assertions
assertDatabaseHas('businesses', ['name' => 'Test']);
assertDatabaseMissing('businesses', ['name' => 'Deleted']);
assertDatabaseCount('businesses', 5);
```

### Custom Assertions

```php
<?php

// Assert response
$response->assertSuccessful();
$response->assertOk(); // 200
$response->assertCreated(); // 201
$response->assertNoContent(); // 204
$response->assertNotFound(); // 404
$response->assertForbidden(); // 403
$response->assertUnauthorized(); // 401
$response->assertUnprocessable(); // 422

// Assert redirects
$response->assertRedirect('/dashboard');
$response->assertRedirectToRoute('home');

// Assert session
$response->assertSessionHas('success');
$response->assertSessionHasErrors(['name', 'email']);
$response->assertSessionDoesntHaveErrors();

// Assert view data
$response->assertViewIs('businesses.index');
$response->assertViewHas('businesses');
$response->assertViewHas('businesses', function ($businesses) {
    return $businesses->count() === 3;
});

// Assert see text
$response->assertSee('Welcome');
$response->assertSeeText('Dashboard');
$response->assertDontSee('Error');
```

### Expectation API

```php
<?php

// Basic expectations
expect($value)->toBe('expected');
expect($value)->toEqual($other);
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->toBeEmpty();

// Type expectations
expect($value)->toBeString();
expect($value)->toBeInt();
expect($value)->toBeFloat();
expect($value)->toBeBool();
expect($value)->toBeArray();
expect($value)->toBeObject();
expect($value)->toBeInstanceOf(Business::class);

// Collection expectations
expect($collection)->toHaveCount(3);
expect($array)->toContain('value');
expect($array)->toHaveKey('key');

// Numeric expectations
expect($number)->toBeGreaterThan(5);
expect($number)->toBeLessThan(10);
expect($number)->toBeGreaterThanOrEqual(5);
expect($number)->toBeBetween(1, 10);

// String expectations
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
expect($string)->toContain('substring');
expect($string)->toMatch('/pattern/');

// Exception expectations
expect(fn() => throw new Exception('error'))
    ->toThrow(Exception::class, 'error');
```

## Multi-Tenancy Testing

```php
<?php

it('scopes businesses to current tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $business1 = Business::factory()->create(['tenant_id' => $tenant1->id]);
    $business2 = Business::factory()->create(['tenant_id' => $tenant2->id]);

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);

    actingAs($user1);

    $businesses = Business::all();

    expect($businesses)->toHaveCount(1)
        ->and($businesses->first()->id)->toBe($business1->id);
});

it('prevents accessing other tenant data', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $business2 = Business::factory()->create(['tenant_id' => $tenant2->id]);

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);

    actingAs($user1);

    get(route('owner.businesses.show', $business2))
        ->assertForbidden();
});
```

## Test Organization Best Practices

### Using beforeEach/afterEach

```php
<?php

beforeEach(function () {
    // Runs before each test
    $this->user = User::factory()->create();
    actingAs($this->user);
});

afterEach(function () {
    // Runs after each test (cleanup)
});
```

### Grouping Tests

```php
<?php

describe('Business CRUD', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    it('creates a business', function () {
        // Test create
    });

    it('updates a business', function () {
        // Test update
    });

    it('deletes a business', function () {
        // Test delete
    });
});

describe('Business Authorization', function () {
    it('allows owner to access', function () {
        // Test authorization
    });
});
```

## Running Tests

```bash
# Run all tests
composer test
./vendor/bin/pest

# Run specific file
./vendor/bin/pest tests/Feature/BusinessTest.php

# Run specific test by name
./vendor/bin/pest --filter="create business"

# Run tests in parallel (faster)
./vendor/bin/pest --parallel

# Run with coverage
./vendor/bin/pest --coverage

# Run only unit tests
./vendor/bin/pest tests/Unit

# Run only feature tests
./vendor/bin/pest tests/Feature
```

## Security Best Practices for Seeders

### ❌ CRITICAL - Never Use Weak Default Passwords

**WRONG - Security Risk:**

```php
<?php

// ❌ CRITICAL SECURITY RISK - Hardcoded weak password
$adminUser = User::query()->firstOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Admin User',
        'password' => Hash::make('admin'), // ❌ NEVER DO THIS
        'email_verified_at' => now(),
    ]
);
```

**Problems:**
- Weak password (`admin`) is easily guessable
- Same password in all environments (dev, staging, production)
- Security vulnerability if seeded in production
- No way to change it without code modification

### ✅ CORRECT - Environment-Based Strong Passwords

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ CRITICAL: Require strong password in production
        if (app()->environment('production') && empty(config('app.seeder.admin_password'))) {
            throw new \RuntimeException(
                'SEED_ADMIN_PASSWORD environment variable must be set to seed admin in production.'
            );
        }

        // ✅ Use config password (from env) in production, generate strong password in dev
        $password = (string) config('app.seeder.admin_password', Str::password(20));

        // Create admin user
        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Assign role
        $adminUser->assignRole('super_admin');

        // ✅ IMPORTANT: Log password only in non-production environments
        if (!app()->environment('production')) {
            $this->command->info("Admin password: {$password}");
        }
    }
}
```

### Configuration File Setup

**CRITICAL:** Never use `env()` directly in code. Always use `config()` helper.

```php
<?php

// config/app.php
return [
    // ... other config

    /*
    |--------------------------------------------------------------------------
    | Database Seeder Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database seeders, including admin user password.
    | In production, SEED_ADMIN_PASSWORD must be defined. In development,
    | a strong random password will be generated if not provided.
    |
    */

    'seeder' => [
        'admin_password' => env('SEED_ADMIN_PASSWORD'),
    ],
];
```

### Environment Configuration

```bash
# .env.example
SEED_ADMIN_PASSWORD=

# .env (development)
SEED_ADMIN_PASSWORD=dev-admin-password-123

# .env (production)
SEED_ADMIN_PASSWORD=your-super-strong-password-here-9a8f7d6e5c4b3a2
```

### Security Checklist for Seeders

✅ **DO:**
- Use `config()` helper to access environment variables (NEVER use `env()` directly)
- Define seeder config in `config/app.php` with `env('SEED_ADMIN_PASSWORD')`
- Generate strong random passwords in development (`Str::password(20)`)
- Require `SEED_ADMIN_PASSWORD` in production
- Log generated passwords only in development
- Use different passwords per environment
- Document password requirements in `.env.example`

❌ **DON'T:**
- **Don't use `env()` directly in code** (always use `config()` helper instead)
- Don't hardcode passwords (especially weak ones like "admin", "password", "123456")
- Don't use the same password across environments
- Don't seed admin users in production without explicit password
- Don't log passwords in production
- Don't commit `.env` files with real passwords

### Testing Seeders

```php
<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('creates admin user with environment password', function () {
    config(['app.env' => 'production']);
    putenv('SEED_ADMIN_PASSWORD=test-password-123');

    (new DatabaseSeeder())->run();

    $admin = User::where('email', 'admin@admin.com')->first();

    expect($admin)->not->toBeNull()
        ->and(Hash::check('test-password-123', $admin->password))->toBeTrue()
        ->and($admin->hasRole('super_admin'))->toBeTrue();

    putenv('SEED_ADMIN_PASSWORD='); // Clean up
});

it('throws exception when seeding in production without password', function () {
    config(['app.env' => 'production']);
    putenv('SEED_ADMIN_PASSWORD=');

    expect(fn() => (new DatabaseSeeder())->run())
        ->toThrow(\RuntimeException::class, 'SEED_ADMIN_PASSWORD');

    putenv('SEED_ADMIN_PASSWORD='); // Clean up
});

it('generates random password in development', function () {
    config(['app.env' => 'local']);
    putenv('SEED_ADMIN_PASSWORD=');

    (new DatabaseSeeder())->run();

    $admin = User::where('email', 'admin@admin.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->password)->not->toBeEmpty();

    putenv('SEED_ADMIN_PASSWORD='); // Clean up
});
```

## Best Practices

### ✅ DO

- Test all code paths (happy, failure, edge cases)
- Use factories for test data
- Use descriptive test names
- Use datasets for similar scenarios
- Use fakes instead of mocking when possible
- Test multi-tenancy isolation
- Test authorization (policies)
- Test validation rules
- Keep tests fast (use `RefreshDatabase`)
- Test one thing per test
- Use expectation API for better readability
- **Use environment variables for seeder passwords**
- **Generate strong random passwords in development**
- **Require explicit passwords in production**
- **Test seeder security**

### ❌ DON'T

- Don't skip tests
- Don't use real external services (use fakes)
- Don't test framework code
- Don't create overly complex tests
- Don't test implementation details
- Don't forget to test edge cases
- Don't use production database
- Don't share state between tests
- Don't hardcode IDs or values
- Don't forget to test translations
- **Don't hardcode weak passwords in seeders**
- **Don't use the same password across environments**
- **Don't seed production without explicit passwords**

## Quick Reference Checklist

Before finalizing ANY feature:

- [ ] Feature tests written for HTTP endpoints
- [ ] Unit tests written for models/DTOs/enums
- [ ] Action tests written (business logic)
- [ ] Policy tests written (authorization)
- [ ] Validation rules tested
- [ ] Multi-tenancy isolation tested
- [ ] Happy path tested
- [ ] Failure paths tested
- [ ] Edge cases tested
- [ ] All tests passing (`composer test`)
- [ ] No N+1 queries (use eager loading)
- [ ] Factories used for test data

## Common Test Patterns

```php
<?php

// Feature test pattern
it('performs action successfully', function () {
    // Arrange: Set up test data
    $user = User::factory()->create();
    actingAs($user);

    // Act: Perform the action
    $response = post('/endpoint', $data);

    // Assert: Verify outcome
    $response->assertSuccessful();
    assertDatabaseHas('table', ['field' => 'value']);
});

// Unit test pattern
it('calculates correctly', function () {
    // Arrange
    $input = 5;

    // Act
    $result = calculate($input);

    // Assert
    expect($result)->toBe(10);
});

// Policy test pattern
it('authorizes action', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->create();

    $policy = new ResourcePolicy();

    expect($policy->update($user, $resource))->toBeTrue();
});
```
