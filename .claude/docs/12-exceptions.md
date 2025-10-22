# Exceptions

## Core Principles

**CRITICAL:** Use specialized exceptions for domain-specific errors - NEVER use generic `Exception`.

- **Domain Exceptions** - Create custom exceptions for business rules violations
- **HTTP Status Mapping** - Map exceptions to appropriate HTTP status codes
- **Renderable** - Implement `render()` method for custom error responses
- **Reportable** - Implement `report()` method for logging/monitoring
- **Localized Messages** - Use `__()` for exception messages (EN, ES, PT-BR)

## Exception Structure

```
app/
├── Exceptions/
│   ├── Handler.php                    # Global exception handler
│   ├── Business/
│   │   ├── BusinessLimitExceededException.php
│   │   ├── BusinessNotFoundException.php
│   │   └── InvalidBusinessTypeException.php
│   ├── Tenant/
│   │   ├── TenantInactiveException.php
│   │   ├── TenantSuspendedException.php
│   │   └── UnauthorizedTenantAccessException.php
│   ├── Menu/
│   │   ├── MenuItemNotFoundException.php
│   │   ├── InvalidPriceException.php
│   │   └── MenuItemUnavailableException.php
│   └── Billing/
│       ├── InsufficientCreditsException.php
│       ├── PlanLimitExceededException.php
│       └── PaymentFailedException.php
```

## Creating Custom Exceptions

```bash
php artisan make:exception Business/BusinessLimitExceededException
```

## Exception Pattern

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\Business;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BusinessLimitExceededException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        public readonly int $currentCount,
        public readonly int $maxAllowed,
        public readonly string $planName,
    ) {
        parent::__construct(
            message: __('exceptions.business_limit_exceeded', [
                'current' => $this->currentCount,
                'max' => $this->maxAllowed,
                'plan' => $this->planName,
            ]),
            code: 403
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'current_count' => $this->currentCount,
                'max_allowed' => $this->maxAllowed,
                'plan_name' => $this->planName,
            ], 403);
        }

        return back()->with('error', $this->getMessage());
    }

    /**
     * Report the exception (logging/monitoring).
     */
    public function report(): void
    {
        // Log or send to monitoring service
        \Log::warning('Business limit exceeded', [
            'current' => $this->currentCount,
            'max' => $this->maxAllowed,
            'plan' => $this->planName,
        ]);
    }
}
```

## Usage in Actions

```php
<?php

declare(strict_types=1);

namespace App\Actions\Business;

use App\DataObjects\Business\CreateBusinessData;
use App\Exceptions\Business\BusinessLimitExceededException;
use App\Models\Business;
use App\Models\Tenant;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateBusinessAction
{
    use AsAction;

    public function handle(Tenant $tenant, CreateBusinessData $data): Business
    {
        // Check business limit
        $currentCount = $tenant->businesses()->count();
        $maxAllowed = $tenant->plan->limits['businesses'] ?? 0;

        if ($currentCount >= $maxAllowed) {
            throw new BusinessLimitExceededException(
                currentCount: $currentCount,
                maxAllowed: $maxAllowed,
                planName: $tenant->plan->name,
            );
        }

        // Create business
        return $tenant->businesses()->create($data->toArray());
    }
}
```

## Common Exception Types

### Domain/Business Rule Exceptions

```php
// When resource not found
throw new BusinessNotFoundException($id);

// When validation fails
throw new InvalidBusinessTypeException($type);

// When limit exceeded
throw new BusinessLimitExceededException($current, $max, $plan);
```

### Authorization Exceptions

```php
// When tenant access denied
throw new UnauthorizedTenantAccessException($tenantId);

// When tenant is inactive
throw new TenantInactiveException($tenant);

// When tenant is suspended
throw new TenantSuspendedException($tenant, $reason);
```

### Payment/Billing Exceptions

```php
// When credits insufficient
throw new InsufficientCreditsException($required, $available);

// When plan limit exceeded
throw new PlanLimitExceededException($resource, $limit);

// When payment fails
throw new PaymentFailedException($transactionId, $reason);
```

## Exception Naming Conventions

| Type                  | Convention                | Example                                   |
|-----------------------|---------------------------|-------------------------------------------|
| **Not Found**         | `*NotFoundException`      | `BusinessNotFoundException`               |
| **Invalid Input**     | `Invalid*Exception`       | `InvalidBusinessTypeException`            |
| **Limit Exceeded**    | `*LimitExceededException` | `BusinessLimitExceededException`          |
| **Unauthorized**      | `Unauthorized*Exception`  | `UnauthorizedTenantAccessException`       |
| **Payment**           | `Payment*Exception`       | `PaymentFailedException`                  |
| **Resource State**    | `*InactiveException`      | `TenantInactiveException`                 |
| **Business Rule**     | Descriptive name          | `MenuItemUnavailableException`            |

## Translation Files

```php
// lang/en/exceptions.php
return [
    'business_limit_exceeded' => 'Business limit exceeded. You have :current businesses, but your :plan plan allows only :max.',
    'business_not_found' => 'Business not found.',
    'invalid_business_type' => 'Invalid business type: :type',
    'tenant_inactive' => 'Your account is inactive. Please contact support.',
    'tenant_suspended' => 'Your account has been suspended. Reason: :reason',
    'insufficient_credits' => 'Insufficient credits. Required: :required, Available: :available',
];

// lang/es/exceptions.php
return [
    'business_limit_exceeded' => 'Límite de negocios excedido. Tienes :current negocios, pero tu plan :plan permite solo :max.',
    // ...
];

// lang/pt_BR/exceptions.php
return [
    'business_limit_exceeded' => 'Limite de negócios excedido. Você tem :current negócios, mas seu plano :plan permite apenas :max.',
    // ...
];
```

## HTTP Status Code Mapping

| Exception Type          | HTTP Status | Description                  |
|-------------------------|-------------|------------------------------|
| **NotFoundException**   | 404         | Resource not found           |
| **Unauthorized**        | 403         | Access denied                |
| **ValidationException** | 422         | Validation failed            |
| **LimitExceeded**       | 403         | Resource limit exceeded      |
| **PaymentFailed**       | 402         | Payment required             |
| **InactiveException**   | 403         | Account/resource inactive    |
| **SuspendedException**  | 403         | Account suspended            |

## Testing Exceptions

```php
<?php

use App\Actions\Business\CreateBusinessAction;
use App\Exceptions\Business\BusinessLimitExceededException;

it('throws exception when business limit exceeded', function () {
    $tenant = Tenant::factory()->create(['plan_id' => 1]);
    $tenant->plan->update(['limits' => ['businesses' => 2]]);

    // Create 2 businesses (at limit)
    Business::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    // Try to create 3rd business
    $data = CreateBusinessData::fromArray([
        'name' => 'New Business',
        'type' => 'restaurant',
    ]);

    expect(fn() => CreateBusinessAction::run($tenant, $data))
        ->toThrow(BusinessLimitExceededException::class, 'Business limit exceeded');
});

it('includes exception context in response', function () {
    $tenant = Tenant::factory()->create(['plan_id' => 1]);
    Business::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    try {
        CreateBusinessAction::run($tenant, $data);
    } catch (BusinessLimitExceededException $e) {
        expect($e->currentCount)->toBe(2)
            ->and($e->maxAllowed)->toBe(2)
            ->and($e->planName)->toBe('Basic');
    }
});
```

## Best Practices

### ✅ DO

- Create domain-specific exceptions for each business rule violation
- Include context data in exception constructor
- Implement `render()` for custom responses
- Implement `report()` for logging/monitoring
- Use translation keys for messages
- Test exception throwing in Actions
- Map exceptions to appropriate HTTP status codes

### ❌ DON'T

- Don't use generic `Exception` or `RuntimeException`
- Don't hardcode exception messages
- Don't put business logic in exception classes
- Don't catch exceptions just to re-throw
- Don't create exceptions for every validation rule (use Form Requests)
- Don't forget to translate messages

## Global Exception Handler

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Business\BusinessLimitExceededException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        BusinessLimitExceededException::class => 'warning',
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        // Exceptions that shouldn't be logged
    ];

    /**
     * Register the exception handling callbacks.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Send to monitoring service (Sentry, Bugsnag, etc.)
        });
    }
}
```

## Quick Reference

### When to Create Custom Exceptions

✅ Business rule violations (limits, constraints) \
✅ Domain-specific errors (invalid state transitions) \
✅ Authorization failures (tenant access, ownership) \
✅ Payment/billing errors \
✅ Resource not found (when generic 404 isn't enough)

❌ Validation errors (use Form Requests) \
❌ Database connection errors (framework handles) \
❌ Generic errors (use built-in exceptions)
