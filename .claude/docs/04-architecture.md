# Architecture & Project Structure

## Stack

**Stack:** Laravel 12 + React + Inertia.js + Filament 4 + Tailwind 4

### Key Points

- **Admin Panel:** Filament 4 (Super Admin)
- **Actions Pattern:** Laravel Actions (lorisleiva/laravel-actions)
- **Testing:** Pest PHP
- **i18n:** English, Spanish, Portuguese BR

### Project Structure

```
app/
├── Actions/          # Business logic (NOT Services!)
│   ├── Business/
│   ├── Tenant/
│   └── Billing/
├── DataObjects/      # Value Objects (DTOs)
│   ├── Business/
│   ├── Menu/
│   └── Order/
├── Enums/            # All enums (descriptive names, no suffix)
├── Events/           # Domain events (past tense)
├── Listeners/        # Event listeners (imperative)
├── Models/           # Eloquent models (thin, no business logic)
├── Observers/        # Model lifecycle observers
├── Http/
│   ├── Controllers/  # Thin orchestrators
│   └── Requests/     # Form Requests (validation)
└── Policies/         # Authorization logic
```

---

## Coding Standards

### Core Rules

- PHP 8.4+, strict types: `declare(strict_types=1);`
- Follow pint.json, PHPStan max level
- No `DB::`, use `Model::query()`
- No `env()` outside config files
- No new folders/dependencies without approval
- Delete .gitkeep when adding files

### Laravel 12 Specific

- No `app\Console\Kernel.php` - use `bootstrap/app.php`
- Commands auto-register from `app/Console/Commands/`
- Use `config('app.name')` not `env('APP_NAME')`

---

## Architecture Summary

This project follows **clean architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                      HTTP REQUEST                            │
└──────────────────────┬──────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  CONTROLLER (Thin Orchestrator)                              │
│  ├─ Validate (FormRequest)                                   │
│  ├─ Authorize (Policy)                                       │
│  ├─ Build Value Object                                       │
│  └─ Call Action                                              │
└──────────────────────┬──────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  VALUE OBJECT (Data Transfer)                                │
│  ├─ Type-safe data structure                                 │
│  ├─ Immutable (readonly)                                     │
│  └─ Factory methods (fromRequest, toArray)                   │
└──────────────────────┬──────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  ACTION (Business Logic)                                     │
│  ├─ Single responsibility                                    │
│  ├─ Database operations                                      │
│  ├─ Business rules & calculations                            │
│  ├─ Call other Actions if needed                             │
│  └─ Dispatch Events                                          │
└──────────────────────┬──────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  MODEL (Data + Relationships)                                │
│  ├─ Eloquent relationships                                   │
│  ├─ Accessors & Mutators                                     │
│  ├─ Scopes                                                   │
│  └─ NO business logic                                        │
└──────────────────────┬──────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  OBSERVER / EVENT (Side Effects)                             │
│  ├─ Model lifecycle hooks (Observer)                         │
│  ├─ Send notifications                                       │
│  ├─ Update related records                                   │
│  └─ Log activities                                           │
└─────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

| Layer              | Responsibility         | ✅ Should                               | ❌ Should NOT                         |
|--------------------|------------------------|----------------------------------------|--------------------------------------|
| **Controller**     | HTTP orchestration     | Validate, Authorize, Delegate, Respond | Business logic, Database queries     |
| **Value Object**   | Data transfer          | Type safety, Immutability              | Validation, Business logic           |
| **Action**         | Business logic         | CRUD, Calculations, Rules, Events      | HTTP concerns, Validation            |
| **Model**          | Data + Relations       | Relationships, Casts, Scopes           | Business logic, Complex calculations |
| **Observer**       | Side effects           | Lifecycle hooks, Events                | Core business logic                  |
| **Event/Listener** | Decoupled side effects | Notifications, Logging, Async tasks    | Main business flow                   |

---

## Quick Reference

### Naming Conventions Summary

| Type             | Convention            | Example                                         |
|------------------|-----------------------|-------------------------------------------------|
| **Enum**         | No suffix (Spatie)    | `BusinessType`, `UserRole`                      |
| **Action**       | `Action` suffix       | `CreateBusinessAction`, `UpdateOrderAction`     |
| **Value Object** | `Data` suffix         | `CreateBusinessData`, `UpdateOrderData`         |
| **Controller**   | Plural + `Controller`  | `BusinessesController`, `OrdersController`      |
| **Event**        | Past tense, no suffix | `BusinessCreated`, `OrderPlaced`                |
| **Listener**     | Imperative, no suffix | `SendWelcomeEmail`, `NotifyAdmin`               |
| **Observer**     | `Observer` suffix     | `BusinessObserver`, `OrderObserver`             |
| **Policy**       | `Policy` suffix       | `BusinessPolicy`, `OrderPolicy`                 |
| **Form Request** | `Request` suffix      | `StoreBusinessRequest`, `UpdateBusinessRequest` |

**NOTE:** NEVER use `Service` suffix - use Actions instead!

### Common Commands

```bash
# Create Model + Migration + Factory
php artisan make:model Product -mf

# Create Action
php artisan make:action CreateProduct

# Create Value Object
php artisan make:class DataObjects/Product/CreateProductData

# Create Form Request
php artisan make:request StoreProductRequest

# Create Policy
php artisan make:policy ProductPolicy --model=Product

# Create Observer
php artisan make:observer ProductObserver --model=Product

# Create Test
php artisan make:test --pest ProductTest
```

### Completion Checklist

Before finalizing ANY feature:

- [ ] Tests passing (`composer test`)
- [ ] Code fixed (`composer fix`)
- [ ] Eager loading implemented (no N+1)
- [ ] Translations added (EN, ES, PT-BR)
- [ ] IMPLEMENTATION.md updated
- [ ] Documentation committed separately
- [ ] No unapproved dependencies
