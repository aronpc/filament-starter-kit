# Actions, Events & Jobs

**CRITICAL:** Use [Laravel Actions](https://laravelactions.com/) - NEVER create traditional Services.

## Core Principles

1. **Actions over Services** - Always use `lorisleiva/laravel-actions`
2. **Event-Driven** - Use Events + Listeners for side effects
3. **Observers** - Use for model lifecycle hooks
4. **Jobs** - Queue via Actions implementing `ShouldQueue`

## Creating Actions

```bash
php artisan make:action CreateBusinessAction
```

## Action Structure

```php
<?php

declare(strict_types=1);

namespace App\Actions\Business;

use App\DataObjects\Business\CreateBusinessData;
use App\Models\Business;
use App\Models\Tenant;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateBusinessAction
{
    use AsAction;

    /**
     * Execute the action.
     */
    public function handle(Tenant $tenant, CreateBusinessData $data): Business
    {
        // Check limits
        if (!$tenant->isWithinLimit('businesses')) {
            throw new \Exception('Business limit exceeded');
        }

        // Create business
        $business = $tenant->businesses()->create($data->toArray());

        // Increment usage
        $tenant->incrementUsage('businesses');

        // Dispatch event
        event(new BusinessCreated($business));

        return $business;
    }

    /**
     * Use as controller (optional).
     */
    public function asController(StoreBusinessRequest $request): Business
    {
        $data = CreateBusinessData::fromRequest($request->validated());
        return $this->handle(auth()->user()->tenant, $data);
    }

    /**
     * Run as queued job (optional).
     */
    public function asJob(Tenant $tenant, CreateBusinessData $data): void
    {
        $this->handle($tenant, $data);
    }
}
```

## Action Usage

```php
// Run synchronously
CreateBusinessAction::run($tenant, $data);

// Run in background (queued)
CreateBusinessAction::dispatch($tenant, $data);

// Run with delay
CreateBusinessAction::dispatch($tenant, $data)->delay(now()->addMinutes(5));

// Run on specific queue
CreateBusinessAction::dispatch($tenant, $data)->onQueue('high');
```

---

## Value Objects (DTOs)

**CRITICAL:** Actions MUST use Value Objects - NEVER pass raw arrays.

### Why Value Objects?

✅ Type Safety - Catch errors at compile time \
✅ IDE Support - Autocompletion \
✅ Validation - Centralized \
✅ Immutability - Prevent mutations \
✅ Self-Documenting - Clear contract \
✅ Reusability - Use across Actions/Jobs/Events

### Creating Value Objects

```php
<?php

declare(strict_types=1);

namespace App\DataObjects\Business;

final readonly class CreateBusinessData
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $email = null,
        public ?string $phone = null,
        public ?array $settings = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            settings: $data['settings'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'settings' => $this->settings ?? [],
        ];
    }
}
```

### When to Use Value Objects

✅ Action parameters (always for complex inputs) \
✅ Service methods \
✅ API responses \
✅ Events payloads \
✅ Jobs data

❌ Single primitive values \
❌ Eloquent models \
❌ Collections

---

## Events & Listeners

**Event:**

```php
<?php

namespace App\Events;

use App\Models\Business;
use Illuminate\Foundation\Events\Dispatchable;

final class BusinessCreated
{
    use Dispatchable;

    public function __construct(public Business $business) {}
}
```

**Listener (as Action):**

```php
<?php

namespace App\Listeners;

use App\Events\BusinessCreated;
use Lorisleiva\Actions\Concerns\AsAction;

final class SendBusinessWelcomeEmail
{
    use AsAction;

    public function handle(BusinessCreated $event): void
    {
        // Send welcome email
    }
}
```

---

## Model Observers

```bash
php artisan make:observer BusinessObserver --model=Business
```

```php
<?php

namespace App\Observers;

use App\Models\Business;

final class BusinessObserver
{
    public function creating(Business $business): void
    {
        $business->slug = Str::slug($business->name);
    }

    public function created(Business $business): void
    {
        event(new BusinessCreated($business));
    }
}
```

**Register in AppServiceProvider:**

```php
public function boot(): void
{
    Business::observe(BusinessObserver::class);
}
```
