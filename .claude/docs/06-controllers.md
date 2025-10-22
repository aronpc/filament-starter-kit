# Controllers

**CRITICAL**: Controllers are **thin orchestrators** - NO business logic.

## What Controllers SHOULD Do

✅ Receive HTTP requests \
✅ Validate (via Form Requests) \
✅ Authorize (via Policies) \
✅ Call Actions \
✅ Return responses (Inertia/JSON/redirect)

## What Controllers MUST NOT Do

❌ Database queries (use Actions) \
❌ Complex calculations (use Actions) \
❌ Business rules (use Actions) \
❌ Direct model manipulation (use Actions) \
❌ Email sending (use Actions/Jobs)

## Controller Pattern

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Business\CreateBusinessAction;
use App\Http\Requests\Business\StoreBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class BusinessController
{
    public function index(): Response
    {
        $businesses = auth()->user()->tenant->businesses()
            ->withCount(['locations', 'menuItems'])
            ->latest()
            ->get();

        return Inertia::render('Businesses/Index', [
            'businesses' => $businesses,
        ]);
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        // ✅ GOOD: Thin controller - delegates to Action
        $business = CreateBusinessAction::run(
            tenant: auth()->user()->tenant,
            data: $request->validated()
        );

        return redirect()
            ->route('owner.businesses.index')
            ->with('success', __('messages.business_created'));
    }
}
```

## Request Flow

```
HTTP Request
    ↓
Controller (Thin Orchestrator)
    ├─ Validate (FormRequest)
    ├─ Authorize (Policy)
    ├─ Call Action
    │   ↓
    │   Action (Business Logic)
    │   ├─ Database Operations
    │   ├─ Business Rules
    │   └─ Return Result
    ↓
Return Response (JSON/Redirect)
```

---

## Policies (Authorization)

**CRITICAL:** Policy methods MUST accept the model instance as a second parameter for instance-level checks.

### Creating Policies

```bash
php artisan make:policy UserPolicy --model=User
```

### ✅ CORRECT - Policy with Model Parameter

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any users.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_user');
    }

    /**
     * Determine if user can view the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function view(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('view_user');
    }

    /**
     * Determine if user can create users.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_user');
    }

    /**
     * Determine if user can update the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function update(AuthUser $authUser, User $model): bool
    {
        // Prevent users from editing their own permissions
        if ($authUser->id === $model->id) {
            return false;
        }

        return $authUser->can('update_user');
    }

    /**
     * Determine if user can delete the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function delete(AuthUser $authUser, User $model): bool
    {
        // Prevent self-deletion
        if ($authUser->id === $model->id) {
            return false;
        }

        return $authUser->can('delete_user');
    }

    /**
     * Determine if user can delete any users.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_user');
    }

    /**
     * Determine if user can restore the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function restore(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('restore_user');
    }

    /**
     * Determine if user can restore any users.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_user');
    }

    /**
     * Determine if user can force delete the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function forceDelete(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('force_delete_user');
    }

    /**
     * Determine if user can force delete any users.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_user');
    }

    /**
     * Determine if user can replicate the user.
     * ✅ CORRECT - Accepts model instance
     */
    public function replicate(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('replicate_user');
    }

    /**
     * Determine if user can reorder users.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_user');
    }
}
```

### ❌ WRONG - Missing Model Parameter

```php
<?php

// ❌ WRONG - Instance-level methods missing model parameter
public function view(AuthUser $authUser): bool  // Missing User $model
{
    return $authUser->can('view_user');
}

public function update(AuthUser $authUser): bool  // Missing User $model
{
    return $authUser->can('update_user');
}

public function delete(AuthUser $authUser): bool  // Missing User $model
{
    return $authUser->can('delete_user');
}
```

**Why this is wrong:**
- Laravel/Filament will pass the model instance when calling these methods
- Missing parameter causes "too many arguments" error
- Cannot implement instance-specific checks (e.g., prevent self-deletion)

### Policy Method Types

| Method Type              | Signature                                        | Use Case                          |
|--------------------------|--------------------------------------------------|-----------------------------------|
| **List/Collection**      | `viewAny(AuthUser $authUser): bool`              | Can view list of records          |
| **Instance-Level**       | `view(AuthUser $user, Model $model): bool`       | Can view specific record          |
| **Instance-Level**       | `update(AuthUser $user, Model $model): bool`     | Can update specific record        |
| **Instance-Level**       | `delete(AuthUser $user, Model $model): bool`     | Can delete specific record        |
| **Bulk Operations**      | `deleteAny(AuthUser $authUser): bool`            | Can delete multiple records       |
| **Instance-Level**       | `restore(AuthUser $user, Model $model): bool`    | Can restore specific record       |
| **Bulk Operations**      | `restoreAny(AuthUser $authUser): bool`           | Can restore multiple records      |
| **Instance-Level**       | `forceDelete(AuthUser $user, Model $model): bool`| Can permanently delete record     |
| **Bulk Operations**      | `forceDeleteAny(AuthUser $authUser): bool`       | Can permanently delete multiple   |
| **Instance-Level**       | `replicate(AuthUser $user, Model $model): bool`  | Can replicate specific record     |
| **Collection**           | `reorder(AuthUser $authUser): bool`              | Can reorder records               |

### Registering Policies

```php
<?php

// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
```

### Using Policies in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

final class UserController
{
    public function update(Request $request, User $user)
    {
        // ✅ Automatic authorization via policy
        $this->authorize('update', $user);

        // Update logic...
    }

    public function destroy(User $user)
    {
        // ✅ Automatic authorization via policy
        $this->authorize('delete', $user);

        // Delete logic...
    }
}
```

### Using Policies in Filament

Filament automatically uses policies when they're registered:

```php
<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // ✅ Filament automatically calls UserPolicy methods
    // No additional configuration needed!
}
```

### Testing Policies

```php
<?php

use App\Models\User;
use App\Policies\UserPolicy;

it('allows admin to update any user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();

    $policy = new UserPolicy();

    expect($policy->update($admin, $targetUser))->toBeTrue();
});

it('prevents user from deleting themselves', function () {
    $user = User::factory()->create();

    $policy = new UserPolicy();

    expect($policy->delete($user, $user))->toBeFalse();
});

it('allows admin to delete other users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();

    $policy = new UserPolicy();

    expect($policy->delete($admin, $targetUser))->toBeTrue();
});
```

### Best Practices

#### ✅ DO

- Always include model parameter for instance-level methods
- Implement instance-specific checks (prevent self-deletion, ownership checks)
- Register policies in `AuthServiceProvider`
- Test all policy methods
- Use descriptive permission names
- Use policies in both controllers and Filament resources

#### ❌ DON'T

- Don't omit model parameter from instance-level methods
- Don't skip authorization checks in controllers
- Don't hardcode permission logic in controllers
- Don't forget to test edge cases (self-deletion, ownership)
- Don't bypass policies with `Gate::before()` unless necessary
