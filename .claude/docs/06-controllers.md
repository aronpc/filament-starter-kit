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

