# Enums (ArchTech Enums)

**Stack:** `archtechx/enums` ^1.1 - Enhanced PHP 8.1+ Enums

## Core Principles

- **Always use HasEnumFeatures trait** - Provides all ArchTech Enums functionality
- **Type Safety** - Leverage strict comparisons with `is()`, `isNot()`, `in()`, `notIn()`
- **Naming Convention** - Use descriptive names without prefix or suffix (following Spatie guidelines) (e.g., `UserRole`, `OrderStatus`)
- **Translation** - Implement `label()` method using `__()`
- **Filament Integration** - Implement `HasLabel` and `HasColor` for Filament resources
- **Value Objects** - Use backed enums (string/int) for database storage

## Installation & Setup

The library is already installed via composer:

```bash
composer require archtechx/enums
```

**HasEnumFeatures Trait (app/Contracts/HasEnumFeatures.php):**

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

trait HasEnumFeatures
{
    use Comparable;      // is(), isNot(), in(), notIn()
    use From;            // from(), tryFrom(), fromName(), tryFromName()
    use InvokableCases;  // __invoke(), __callStatic()
    use Metadata;        // fromMeta(), tryFromMeta(), __call()
    use Names;           // names()
    use Options;         // options(), stringOptions()
    use Values;          // values()
}
```

---

## Available Traits

### 1. Comparable - Type-Safe Comparisons

**CRITICAL:** Always use `is()`, `isNot()`, `in()`, `notIn()` for enum comparisons - NEVER use `==` or `===` directly.

```php
<?php

// ✅ CORRECT - Using Comparable methods
if ($status->is(OrderStatus::Pending)) {
    // Handle pending order
}

if ($status->isNot(OrderStatus::Cancelled)) {
    // Process non-cancelled order
}

if ($status->in([OrderStatus::Pending, OrderStatus::Confirmed])) {
    // Handle pending or confirmed
}

if ($status->notIn([OrderStatus::Delivered, OrderStatus::Cancelled])) {
    // Handle active orders
}

// ❌ WRONG - Direct comparison
if ($status === OrderStatus::Pending) { } // Don't do this
if ($status == 'pending') { } // Never do this
```

**Method Reference:**

| Method         | Description                           | Returns |
|----------------|---------------------------------------|---------|
| `is($enum)`    | Check if enum matches given value     | `bool`  |
| `isNot($enum)` | Check if enum does NOT match          | `bool`  |
| `in($enums)`   | Check if enum is in array of values   | `bool`  |
| `notIn($enums)`| Check if enum is NOT in array         | `bool`  |

**Complete Example:**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasEnumFeatures;

enum OrderStatus: string
{
    use HasEnumFeatures;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    // ✅ CORRECT - Using Comparable in custom methods
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            // Using is() for comparison
            self::Pending => $newStatus->in([self::Confirmed, self::Cancelled]),
            self::Confirmed => $newStatus->in([self::Pending, self::Preparing, self::Cancelled]),
            self::Preparing => $newStatus->in([self::Confirmed, self::Ready, self::Cancelled]),
            self::Ready => $newStatus->in([self::Preparing, self::Delivered, self::Cancelled]),
            self::Delivered, self::Cancelled => false,
        };
    }

    public function isFinal(): bool
    {
        // ✅ CORRECT - Using in() for multiple checks
        return $this->in([self::Delivered, self::Cancelled]);
    }

    public function isActive(): bool
    {
        // ✅ CORRECT - Using notIn() for exclusion checks
        return $this->notIn([self::Delivered, self::Cancelled]);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('enums.order_status.pending'),
            self::Confirmed => __('enums.order_status.confirmed'),
            self::Preparing => __('enums.order_status.preparing'),
            self::Ready => __('enums.order_status.ready'),
            self::Delivered => __('enums.order_status.delivered'),
            self::Cancelled => __('enums.order_status.cancelled'),
        };
    }
}
```

**Usage in Controllers/Actions:**

```php
<?php

use App\Enums\OrderStatus;

// ✅ CORRECT - Comparison in controller
public function update(Order $order, UpdateOrderRequest $request): RedirectResponse
{
    $newStatus = OrderStatus::from($request->status);

    // Using is() to check current status
    if ($order->status->is(OrderStatus::Delivered)) {
        throw new Exception('Cannot update delivered order');
    }

    // Using canTransitionTo (which uses in() internally)
    if (!$order->status->canTransitionTo($newStatus)) {
        throw new Exception('Invalid status transition');
    }

    $order->update(['status' => $newStatus]);

    return redirect()->route('orders.show', $order);
}

// ✅ CORRECT - Filtering with in()
public function getActiveOrders(): Collection
{
    return Order::query()
        ->whereIn('status', OrderStatus::values())
        ->get()
        ->filter(fn ($order) => $order->status->isActive());
}

// ✅ CORRECT - Multiple checks with notIn()
public function canEditOrder(Order $order): bool
{
    return $order->status->notIn([
        OrderStatus::Delivered,
        OrderStatus::Cancelled,
    ]);
}
```

---

### 2. From - Creating Instances

**Create enum instances from values or names:**

```php
<?php

// ✅ from() - Create from value (throws ValueError if not found)
$status = OrderStatus::from('pending');

// ✅ tryFrom() - Safe version, returns null if not found
$status = OrderStatus::tryFrom('invalid'); // null

// ✅ fromName() - Create from case name (throws ValueError if not found)
$status = OrderStatus::fromName('Pending');

// ✅ tryFromName() - Safe version, returns null if not found
$status = OrderStatus::tryFromName('Invalid'); // null
```

**Method Reference:**

| Method               | Description                        | Returns        | Throws      |
|----------------------|------------------------------------|----------------|-------------|
| `from($value)`       | Get enum by value                  | `static`       | `ValueError`|
| `tryFrom($value)`    | Get enum by value (safe)           | `?static`      | -           |
| `fromName($name)`    | Get enum by case name              | `static`       | `ValueError`|
| `tryFromName($name)` | Get enum by case name (safe)       | `?static`      | -           |

**Practical Examples:**

```php
<?php

// In controllers
public function store(Request $request): RedirectResponse
{
    // ✅ CORRECT - Using from() with validation
    $type = BusinessType::from($request->type);

    $business = Business::create([
        'type' => $type,
        // ...
    ]);

    return redirect()->route('businesses.index');
}

// In Form Requests
public function rules(): array
{
    return [
        // ✅ CORRECT - Validation rule with values()
        'status' => ['required', 'in:' . implode(',', OrderStatus::values())],
    ];
}

// Safe conversion from user input
public function updateStatus(Request $request): JsonResponse
{
    // ✅ CORRECT - Using tryFrom() for safe conversion
    $status = OrderStatus::tryFrom($request->status);

    if (!$status) {
        return response()->json(['error' => 'Invalid status'], 422);
    }

    // Use $status safely
    return response()->json(['status' => $status->label()]);
}

// Converting from name (useful for API integrations)
public function createFromApi(array $data): Business
{
    // ✅ CORRECT - Using fromName() when API uses case names
    $type = BusinessType::fromName($data['businessType']);

    return Business::create(['type' => $type]);
}
```

---

### 3. Values - Get Array of Values

**Get all enum values as array:**

```php
<?php

// ✅ values() - Get array of all case values
$values = OrderStatus::values();
// ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled']

// For pure enums (no backing value), returns case names
$names = PureEnum::values();
// ['CaseOne', 'CaseTwo', 'CaseThree']
```

**Method Reference:**

| Method      | Description                  | Returns       |
|-------------|------------------------------|---------------|
| `values()`  | Get array of all case values | `array`       |

**Practical Examples:**

```php
<?php

// ✅ Validation rules
public function rules(): array
{
    return [
        'status' => ['required', 'in:' . implode(',', OrderStatus::values())],
        'type' => ['required', Rule::in(BusinessType::values())],
    ];
}

// ✅ Database query
public function getActiveOrders(): Collection
{
    return Order::whereIn('status', OrderStatus::values())->get();
}

// ✅ Checking if value is valid
public function isValidStatus(string $status): bool
{
    return in_array($status, OrderStatus::values(), true);
}

// ✅ API response
public function getAvailableStatuses(): JsonResponse
{
    return response()->json([
        'statuses' => OrderStatus::values(),
    ]);
}
```

---

### 4. Names - Get Array of Case Names

**Get all enum case names as array:**

```php
<?php

// ✅ names() - Get array of all case names
$names = OrderStatus::names();
// ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled']
```

**Method Reference:**

| Method     | Description                   | Returns  |
|------------|-------------------------------|----------|
| `names()`  | Get array of all case names   | `array`  |

**Practical Examples:**

```php
<?php

// ✅ Debugging/Logging
Log::info('Available statuses', [
    'names' => OrderStatus::names(),
    'values' => OrderStatus::values(),
]);

// ✅ Documentation generation
public function getEnumDocumentation(): array
{
    return [
        'enum' => OrderStatus::class,
        'cases' => OrderStatus::names(),
        'values' => OrderStatus::values(),
    ];
}

// ✅ Testing
it('has all expected case names', function () {
    $names = OrderStatus::names();

    expect($names)->toContain('Pending', 'Confirmed', 'Delivered');
});
```

---

### 5. Options - Get Associative Array

**Get associative array of case names => values:**

```php
<?php

// ✅ options() - Get [case name => case value]
$options = OrderStatus::options();
// [
//     'Pending' => 'pending',
//     'Confirmed' => 'confirmed',
//     'Preparing' => 'preparing',
//     'Ready' => 'ready',
//     'Delivered' => 'delivered',
//     'Cancelled' => 'cancelled',
// ]

// ✅ stringOptions() - Generate HTML options
$html = OrderStatus::stringOptions();
// <option value="pending">Pending</option>
// <option value="confirmed">Confirmed</option>
// ...

// Custom format
$html = OrderStatus::stringOptions(
    callback: fn($name, $value) => "<option value=\"{$value}\" data-name=\"{$name}\">{$name}</option>",
    glue: "\n"
);
```

**Method Reference:**

| Method             | Description                            | Returns  |
|--------------------|----------------------------------------|----------|
| `options()`        | Get [name => value] array              | `array`  |
| `stringOptions()`  | Generate HTML option tags              | `string` |

**Practical Examples:**

```php
<?php

// ✅ Blade select dropdown (traditional approach)
<select name="status">
    @foreach(OrderStatus::options() as $name => $value)
        <option value="{{ $value }}">{{ __("enums.order_status.{$value}") }}</option>
    @endforeach
</select>

// ✅ Filament Select (preferred)
Forms\Components\Select::make('status')
    ->label(__('fields.status'))
    ->options(function () {
        // Convert to [value => label] format
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    });

// ✅ API endpoint
public function getStatusOptions(): JsonResponse
{
    $options = collect(OrderStatus::cases())
        ->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ])
        ->values();

    return response()->json($options);
}

// ✅ Testing
it('has correct options structure', function () {
    $options = OrderStatus::options();

    expect($options)
        ->toBeArray()
        ->toHaveKey('Pending', 'pending')
        ->toHaveKey('Confirmed', 'confirmed');
});
```

---

### 6. InvokableCases - Invoke Enums

**Call enum cases as methods or invoke them:**

```php
<?php

// ✅ __invoke() - Get value by invoking enum instance
$status = OrderStatus::Pending;
$value = $status(); // 'pending'

// ✅ __callStatic() - Get value by calling case as static method
$value = OrderStatus::Pending(); // 'pending'
$value = OrderStatus::Confirmed(); // 'confirmed'
```

**Method Reference:**

| Method           | Description                       | Returns       |
|------------------|-----------------------------------|---------------|
| `__invoke()`     | Get value when invoked `$enum()`  | `mixed`       |
| `__callStatic()` | Get value via `Enum::Case()`      | `mixed`       |

**Practical Examples:**

```php
<?php

// ✅ Quick value access
$pendingValue = OrderStatus::Pending(); // 'pending'

// ✅ In array mapping
$values = array_map(
    fn($case) => $case(),
    OrderStatus::cases()
);

// ⚠️ NOTE: This is less common in modern PHP.
// Prefer explicit ->value access for clarity:
$value = OrderStatus::Pending->value; // More explicit
```

---

### 7. Metadata - Attach Custom Data

**Add custom metadata to enum cases:**

```php
<?php

use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Meta\MetaProperty;

// Define a meta property
#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final class Priority extends MetaProperty
{
    public function __construct(public int $value) {}
}

// Use in enum
enum TaskStatus: string
{
    use HasEnumFeatures;

    #[Priority(1)]
    case Urgent = 'urgent';

    #[Priority(2)]
    case High = 'high';

    #[Priority(3)]
    case Normal = 'normal';

    #[Priority(4)]
    case Low = 'low';
}

// Access metadata
$status = TaskStatus::Urgent;
$priority = $status->priority(); // 1

// Find by metadata
$urgent = TaskStatus::fromMeta(new Priority(1)); // TaskStatus::Urgent
$high = TaskStatus::tryFromMeta(new Priority(2)); // TaskStatus::High
```

**Method Reference:**

| Method              | Description                      | Returns       | Throws       |
|---------------------|----------------------------------|---------------|--------------|
| `fromMeta($meta)`   | Get enum by meta property value  | `static`      | `ValueError` |
| `tryFromMeta($meta)`| Safe version of fromMeta         | `?static`     | -            |
| `__call($method)`   | Access meta property dynamically | `mixed`       | -            |

**Practical Example:**

```php
<?php

// Custom metadata for HTTP status codes
#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final class HttpCode extends MetaProperty
{
    public function __construct(public int $value) {}
}

enum ApiResponseEnum: string
{
    use HasEnumFeatures;

    #[HttpCode(200)]
    case Success = 'success';

    #[HttpCode(400)]
    case BadRequest = 'bad_request';

    #[HttpCode(401)]
    case Unauthorized = 'unauthorized';

    #[HttpCode(404)]
    case NotFound = 'not_found';

    #[HttpCode(500)]
    case ServerError = 'server_error';
}

// Usage in API responses
public function respond(ApiResponseEnum $status, array $data = []): JsonResponse
{
    return response()->json($data, $status->httpCode());
}

// Find status by HTTP code
public function getStatusFromCode(int $code): ?ApiResponseEnum
{
    return ApiResponseEnum::tryFromMeta(new HttpCode($code));
}
```

---

## Best Practices

### ✅ DO

- **Always use `HasEnumFeatures` trait** in all enums
- **Use `is()`, `isNot()`, `in()`, `notIn()`** for comparisons (NEVER `===` or `==`)
- **Use descriptive names without suffix (following Spatie guidelines)** (e.g., `UserRole`, `OrderStatus`)
- **Implement `label()` method** using `__()` for translations
- **Implement `HasLabel` and `HasColor`** for Filament integration
- **Use backed enums** (string/int) for database storage
- **Use `from()`/`tryFrom()`** to create instances from values
- **Use `values()`** for validation rules and queries
- **Use `options()`** for select dropdowns
- **Document custom methods** that use enum features

### ❌ DON'T

- Don't use direct comparison operators (`===`, `==`, `!=`)
- Don't forget to add `HasEnumFeatures` trait
- Don't use suffixes in enum names (follow Spatie guidelines)
- Don't hardcode enum values - use enum cases
- Don't skip translation in `label()` method
- Don't use magic values instead of enum cases
- Don't forget to test enum behavior
- Don't use `$fillable` with enums (use casts)

---

## Complete Example

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasEnumFeatures;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    use HasEnumFeatures;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * Get translated label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('enums.order_status.pending'),
            self::Confirmed => __('enums.order_status.confirmed'),
            self::Preparing => __('enums.order_status.preparing'),
            self::Ready => __('enums.order_status.ready'),
            self::Delivered => __('enums.order_status.delivered'),
            self::Cancelled => __('enums.order_status.cancelled'),
        };
    }

    /**
     * Get Filament badge color.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Preparing => 'primary',
            self::Ready => 'success',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Check if status can transition to new status.
     *
     * ✅ Uses in() for comparison
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Pending => $newStatus->in([self::Confirmed, self::Cancelled]),
            self::Confirmed => $newStatus->in([self::Pending, self::Preparing, self::Cancelled]),
            self::Preparing => $newStatus->in([self::Confirmed, self::Ready, self::Cancelled]),
            self::Ready => $newStatus->in([self::Preparing, self::Delivered, self::Cancelled]),
            self::Delivered, self::Cancelled => false,
        };
    }

    /**
     * Check if status is final (cannot be changed).
     *
     * ✅ Uses in() for multiple checks
     */
    public function isFinal(): bool
    {
        return $this->in([self::Delivered, self::Cancelled]);
    }

    /**
     * Check if status is active (can be modified).
     *
     * ✅ Uses notIn() for exclusion
     */
    public function isActive(): bool
    {
        return $this->notIn([self::Delivered, self::Cancelled]);
    }

    /**
     * Check if status requires preparation.
     *
     * ✅ Uses in() for group checks
     */
    public function requiresPreparation(): bool
    {
        return $this->in([self::Confirmed, self::Preparing, self::Ready]);
    }

    /**
     * Get icon for status.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Confirmed => 'heroicon-o-check-circle',
            self::Preparing => 'heroicon-o-fire',
            self::Ready => 'heroicon-o-shopping-bag',
            self::Delivered => 'heroicon-o-truck',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }
}
```

---

## Testing Enums

```php
<?php

use App\Enums\OrderStatus;

// Test Comparable methods
it('uses is() for comparison', function () {
    $status = OrderStatus::Pending;

    expect($status->is(OrderStatus::Pending))->toBeTrue()
        ->and($status->isNot(OrderStatus::Confirmed))->toBeTrue();
});

it('uses in() for multiple checks', function () {
    $status = OrderStatus::Pending;

    expect($status->in([OrderStatus::Pending, OrderStatus::Confirmed]))->toBeTrue()
        ->and($status->notIn([OrderStatus::Delivered, OrderStatus::Cancelled]))->toBeTrue();
});

// Test custom methods
it('checks status transition', function () {
    $pending = OrderStatus::Pending;
    $confirmed = OrderStatus::Confirmed;
    $delivered = OrderStatus::Delivered;

    expect($pending->canTransitionTo($confirmed))->toBeTrue()
        ->and($delivered->canTransitionTo($pending))->toBeFalse();
});

it('identifies final statuses', function () {
    expect(OrderStatus::Delivered->isFinal())->toBeTrue()
        ->and(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Pending->isFinal())->toBeFalse();
});

// Test values() and names()
it('returns all values', function () {
    $values = OrderStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toContain('pending', 'confirmed', 'delivered');
});

it('returns all case names', function () {
    $names = OrderStatus::names();

    expect($names)->toBeArray()
        ->and($names)->toContain('Pending', 'Confirmed', 'Delivered');
});

// Test from() methods
it('creates enum from value', function () {
    $status = OrderStatus::from('pending');

    expect($status)->toBeInstanceOf(OrderStatus::class)
        ->and($status->is(OrderStatus::Pending))->toBeTrue();
});

it('returns null for invalid value with tryFrom', function () {
    $status = OrderStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});

// Test translations
it('returns translated labels', function () {
    app()->setLocale('en');

    expect(OrderStatus::Pending->label())->toBe('Pending');

    app()->setLocale('es');

    expect(OrderStatus::Pending->label())->toBe('Pendiente');
});

// Test Filament integration
it('returns correct colors', function () {
    expect(OrderStatus::Pending->getColor())->toBe('warning')
        ->and(OrderStatus::Delivered->getColor())->toBe('success')
        ->and(OrderStatus::Cancelled->getColor())->toBe('danger');
});
```

---

## Quick Reference Checklist

Before finalizing ANY enum:

- [ ] Uses `HasEnumFeatures` trait
- [ ] Uses descriptive name without suffix (following Spatie guidelines)
- [ ] Uses `is()`, `isNot()`, `in()`, `notIn()` for comparisons
- [ ] Implements `label()` with `__()`
- [ ] Implements `HasLabel` interface (for Filament)
- [ ] Implements `HasColor` interface (for Filament)
- [ ] Uses backed enum (string/int)
- [ ] All custom methods documented
- [ ] Translations added (EN, ES, PT-BR)
- [ ] Tests written for all methods
- [ ] No direct comparison operators used

---

## Common Patterns Summary

```php
// ✅ Comparison
if ($status->is(OrderStatus::Pending)) { }
if ($status->in([OrderStatus::Pending, OrderStatus::Confirmed])) { }

// ✅ Creation
$status = OrderStatus::from('pending');
$status = OrderStatus::tryFrom($value);

// ✅ Arrays
$values = OrderStatus::values(); // ['pending', 'confirmed', ...]
$names = OrderStatus::names();   // ['Pending', 'Confirmed', ...]
$options = OrderStatus::options(); // ['Pending' => 'pending', ...]

// ✅ Validation
Rule::in(OrderStatus::values())

// ✅ Filament Select
Forms\Components\Select::make('status')
    ->options(fn () => collect(OrderStatus::cases())
        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
    );
```
