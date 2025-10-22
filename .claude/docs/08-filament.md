# Filament 4 (Admin & Owner Panels)

**Stack:** Filament v4 + Livewire v3 + Alpine.js + Tailwind CSS v4

## Structure

```
app/Filament/
├── Owner/                    # Owner Panel (tenant-scoped)
│   ├── Pages/               # Custom pages
│   ├── Resources/           # CRUD resources
│   │   └── Schemas/        # Reusable form/table schemas
│   └── Widgets/             # Dashboard widgets
└── Resources/               # Admin Panel (Super Admin)
    └── Schemas/             # Reusable form/table schemas
```

## Core Principles

- **Resources:** Use for standard CRUD operations
- **Pages:** Use for custom functionality (dashboards, reports, workflows)
- **Widgets:** Use for dashboard stats and charts
- **Schemas:** Extract reusable Forms/Tables into `Schemas/` folder (avoid duplication)

## Filament Rules

- **Enums:** MUST implement `HasColor` + `HasLabel` interfaces
- **Forms:** Extract to `Schemas/NameForm.php` for reusability across create/edit/relation managers
- **Tables:** Extract to `Schemas/NameTable.php` when used in multiple resources
- **Translations:** Use `__()` for all labels, headings, descriptions
- **Tenant Scoping:** Owner panel MUST filter by `tenant_id`
- **Authorization:** Use Policies for resource actions

## Creating Resources

```bash
# Create a resource
php artisan make:filament-resource Business --generate

# Create a resource for Owner panel
php artisan make:filament-resource Business --panel=owner --generate

# Create custom page
php artisan make:filament-page Dashboard --panel=owner

# Create widget
php artisan make:filament-widget StatsOverview --panel=owner
```

## Resource Structure

### Complete Resource Example

```php
<?php

declare(strict_types=1);

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\BusinessResource\Pages;
use App\Models\Business;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    // ✅ CORRECT - Use method for translation
    public static function getNavigationLabel(): string
    {
        return __('navigation.businesses');
    }

    public static function getPluralLabel(): ?string
    {
        return __('navigation.businesses');
    }

    public static function getLabel(): ?string
    {
        return __('messages.resources.business');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('fields.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label(__('fields.type'))
                            ->options(BusinessTypeEnum::toSelectArray())
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label(__('fields.email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('fields.phone'))
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('fields.type'))
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->badge()
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('fields.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('fields.type'))
                    ->options(BusinessTypeEnum::toSelectArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('fields.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }

    // ✅ CORRECT - Tenant scoping for Owner panel
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
```

## Form Components

### Text Input

```php
Forms\Components\TextInput::make('name')
    ->label(__('fields.name'))
    ->placeholder(__('fields.name'))
    ->required()
    ->maxLength(255)
    ->helperText(__('messages.helper_text'))
    ->autocomplete('name');
```

### Select

```php
Forms\Components\Select::make('type')
    ->label(__('fields.type'))
    ->options(BusinessTypeEnum::toSelectArray())
    ->required()
    ->searchable()
    ->native(false);

// With relationship
Forms\Components\Select::make('business_id')
    ->label(__('fields.business'))
    ->relationship('business', 'name')
    ->required()
    ->searchable()
    ->preload();
```

### Textarea

```php
Forms\Components\Textarea::make('description')
    ->label(__('fields.description'))
    ->rows(4)
    ->maxLength(1000)
    ->columnSpanFull();
```

### Toggle & Checkbox

```php
Forms\Components\Toggle::make('is_active')
    ->label(__('fields.is_active'))
    ->default(true)
    ->inline(false);

Forms\Components\Checkbox::make('accept_terms')
    ->label(__('fields.accept_terms'))
    ->required();
```

### Date & Time

```php
Forms\Components\DatePicker::make('opened_at')
    ->label(__('fields.opened_at'))
    ->native(false)
    ->displayFormat('d/m/Y');

Forms\Components\TimePicker::make('opens_at')
    ->label(__('fields.opens_at'))
    ->native(false)
    ->seconds(false);

Forms\Components\DateTimePicker::make('published_at')
    ->label(__('fields.published_at'))
    ->native(false);
```

### File Upload

```php
Forms\Components\FileUpload::make('logo')
    ->label(__('fields.logo'))
    ->image()
    ->imageEditor()
    ->maxSize(1024) // KB
    ->directory('logos')
    ->visibility('public')
    ->acceptedFileTypes(['image/png', 'image/jpeg']);
```

### Rich Editor

```php
Forms\Components\RichEditor::make('content')
    ->label(__('fields.content'))
    ->toolbarButtons([
        'bold',
        'italic',
        'link',
        'bulletList',
        'orderedList',
    ])
    ->columnSpanFull();
```

### Repeater

```php
Forms\Components\Repeater::make('items')
    ->label(__('fields.items'))
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label(__('fields.name'))
            ->required(),

        Forms\Components\TextInput::make('price')
            ->label(__('fields.price'))
            ->numeric()
            ->prefix('$')
            ->required(),
    ])
    ->columns(2)
    ->collapsible()
    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
    ->addActionLabel(__('messages.add_item'))
    ->columnSpanFull();
```

## Layout Components

### Section

```php
Schemas\Components\Section::make()
    ->heading(__('messages.basic_information'))
    ->description(__('messages.basic_information_desc'))
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label(__('fields.name'))
            ->required(),

        Forms\Components\TextInput::make('email')
            ->label(__('fields.email'))
            ->email()
            ->required(),
    ])
    ->columns(2)
    ->collapsible();
```

### Grid

```php
Schemas\Components\Grid::make()
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label(__('fields.name'))
            ->columnSpan(2),

        Forms\Components\TextInput::make('email')
            ->label(__('fields.email'))
            ->columnSpan(1),

        Forms\Components\TextInput::make('phone')
            ->label(__('fields.phone'))
            ->columnSpan(1),
    ])
    ->columns(2);
```

### Tabs

```php
Schemas\Components\Tabs::make()
    ->tabs([
        Schemas\Components\Tabs\Tab::make(__('tabs.basic'))
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('fields.name'))
                    ->required(),
            ]),

        Schemas\Components\Tabs\Tab::make(__('tabs.settings'))
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label(__('fields.is_active')),
            ]),
    ]);
```

## Table Columns

### Text Column

```php
Tables\Columns\TextColumn::make('name')
    ->label(__('fields.name'))
    ->searchable()
    ->sortable()
    ->limit(50)
    ->tooltip(fn ($record) => $record->name);
```

### Badge Column

```php
Tables\Columns\TextColumn::make('status')
    ->label(__('fields.status'))
    ->badge()
    ->color(fn ($state) => $state->color())
    ->formatStateUsing(fn ($state) => $state->label());
```

### Icon Column

```php
Tables\Columns\IconColumn::make('is_active')
    ->label(__('fields.is_active'))
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->trueColor('success')
    ->falseColor('danger');
```

### Image Column

```php
Tables\Columns\ImageColumn::make('logo')
    ->label(__('fields.logo'))
    ->circular()
    ->size(40);
```

### Relationship Column

```php
Tables\Columns\TextColumn::make('business.name')
    ->label(__('fields.business'))
    ->searchable()
    ->sortable();
```

## Table Filters

### Select Filter

```php
Tables\Filters\SelectFilter::make('type')
    ->label(__('fields.type'))
    ->options(BusinessTypeEnum::toSelectArray());

// With relationship
Tables\Filters\SelectFilter::make('business_id')
    ->label(__('fields.business'))
    ->relationship('business', 'name')
    ->searchable()
    ->preload();
```

### Ternary Filter (Yes/No/All)

```php
Tables\Filters\TernaryFilter::make('is_active')
    ->label(__('fields.is_active'))
    ->placeholder(__('messages.all'))
    ->trueLabel(__('messages.active'))
    ->falseLabel(__('messages.inactive'));
```

### Date Filter

```php
Tables\Filters\Filter::make('created_at')
    ->form([
        Forms\Components\DatePicker::make('created_from')
            ->label(__('fields.created_from')),
        Forms\Components\DatePicker::make('created_until')
            ->label(__('fields.created_until')),
    ])
    ->query(function (Builder $query, array $data): Builder {
        return $query
            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    });
```

## Actions

### Table Actions

```php
Tables\Actions\Action::make('activate')
    ->label(__('messages.activate'))
    ->icon('heroicon-o-check')
    ->color('success')
    ->requiresConfirmation()
    ->action(fn (Business $record) => $record->update(['is_active' => true]))
    ->visible(fn (Business $record) => !$record->is_active);

Tables\Actions\EditAction::make(),

Tables\Actions\DeleteAction::make()
    ->requiresConfirmation();
```

### Bulk Actions

```php
Tables\Actions\BulkAction::make('activate')
    ->label(__('messages.activate_selected'))
    ->icon('heroicon-o-check')
    ->color('success')
    ->requiresConfirmation()
    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]));
```

### Action with Form

```php
Tables\Actions\Action::make('sendEmail')
    ->label(__('messages.send_email'))
    ->icon('heroicon-o-envelope')
    ->form([
        Forms\Components\Textarea::make('message')
            ->label(__('fields.message'))
            ->required()
            ->rows(4),
    ])
    ->action(function (Business $record, array $data) {
        // Send email logic
        Notification::make()
            ->title(__('messages.email_sent'))
            ->success()
            ->send();
    });
```

## Schema Pattern (Reusable Components)

### Extracting Form Schema

```php
// app/Filament/Owner/Resources/BusinessResource/Schemas/BusinessForm.php
<?php

declare(strict_types=1);

namespace App\Filament\Owner\Resources\BusinessResource\Schemas;

use App\Enums\BusinessTypeEnum;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

final class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make()
                    ->heading(__('messages.basic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('fields.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label(__('fields.type'))
                            ->options(BusinessTypeEnum::toSelectArray())
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label(__('fields.email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('fields.phone'))
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}

// Usage in Resource
public static function form(Schema $schema): Schema
{
    return BusinessForm::configure($schema);
}
```

### Extracting Table Schema

```php
// app/Filament/Owner/Resources/BusinessResource/Schemas/BusinessTable.php
<?php

declare(strict_types=1);

namespace App\Filament\Owner\Resources\BusinessResource\Schemas;

use App\Enums\BusinessTypeEnum;
use Filament\Tables;

final class BusinessTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('fields.name'))
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('type')
                ->label(__('fields.type'))
                ->formatStateUsing(fn ($state) => $state->label())
                ->badge()
                ->color(fn ($state) => $state->color()),

            Tables\Columns\IconColumn::make('is_active')
                ->label(__('fields.is_active'))
                ->boolean(),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type')
                ->label(__('fields.type'))
                ->options(BusinessTypeEnum::toSelectArray()),

            Tables\Filters\TernaryFilter::make('is_active')
                ->label(__('fields.is_active')),
        ];
    }
}

// Usage in Resource
public static function table(Table $table): Table
{
    return $table
        ->columns(BusinessTable::columns())
        ->filters(BusinessTable::filters());
}
```

## Testing Filament

### Testing Resources

```php
<?php

use App\Filament\Owner\Resources\BusinessResource;
use App\Models\{Business, User};
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render business list page', function () {
    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->assertSuccessful();
});

it('can list businesses', function () {
    $businesses = Business::factory()
        ->count(3)
        ->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->assertCanSeeTableRecords($businesses);
});

it('can search businesses by name', function () {
    $businesses = Business::factory()
        ->count(3)
        ->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->searchTable($businesses->first()->name)
        ->assertCanSeeTableRecords($businesses->take(1))
        ->assertCanNotSeeTableRecords($businesses->skip(1));
});

it('can filter businesses by type', function () {
    $restaurant = Business::factory()
        ->create(['tenant_id' => $this->user->tenant_id, 'type' => 'restaurant']);

    $cafe = Business::factory()
        ->create(['tenant_id' => $this->user->tenant_id, 'type' => 'cafe']);

    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->filterTable('type', 'restaurant')
        ->assertCanSeeTableRecords([$restaurant])
        ->assertCanNotSeeTableRecords([$cafe]);
});

it('can render business create page', function () {
    livewire(BusinessResource\Pages\CreateBusiness::class)
        ->assertSuccessful();
});

it('can create a business', function () {
    $data = [
        'name' => 'Test Business',
        'type' => 'restaurant',
        'email' => 'test@example.com',
        'is_active' => true,
    ];

    livewire(BusinessResource\Pages\CreateBusiness::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('businesses', [
        'name' => 'Test Business',
        'tenant_id' => $this->user->tenant_id,
    ]);
});

it('validates required fields when creating', function () {
    livewire(BusinessResource\Pages\CreateBusiness::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('can render business edit page', function () {
    $business = Business::factory()->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\EditBusiness::class, ['record' => $business->id])
        ->assertSuccessful();
});

it('can update a business', function () {
    $business = Business::factory()->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\EditBusiness::class, ['record' => $business->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($business->fresh()->name)->toBe('Updated Name');
});

it('can delete a business', function () {
    $business = Business::factory()->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\EditBusiness::class, ['record' => $business->id])
        ->callAction('delete');

    $this->assertSoftDeleted($business);
});
```

### Testing Actions

```php
it('can activate a business', function () {
    $business = Business::factory()->inactive()->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->callTableAction('activate', $business);

    expect($business->fresh()->is_active)->toBeTrue();
});

it('can bulk activate businesses', function () {
    $businesses = Business::factory()
        ->count(3)
        ->inactive()
        ->create(['tenant_id' => $this->user->tenant_id]);

    livewire(BusinessResource\Pages\ListBusinesses::class)
        ->callTableBulkAction('activate', $businesses);

    $businesses->each(fn ($b) => expect($b->fresh()->is_active)->toBeTrue());
});
```

## Security Best Practices

### Prevent Self-Deletion in Edit Pages

**CRITICAL:** Always prevent users from deleting their own account and require confirmation for deletions.

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation() // ✅ Require confirmation
                ->modalHeading(__('messages.confirm_delete_user'))
                ->modalDescription(__('messages.confirm_delete_user_description'))
                ->modalSubmitActionLabel(__('messages.delete'))
                ->hidden(fn (): bool => $this->record?->getKey() === Auth::id()), // ✅ Prevent self-deletion
        ];
    }
}
```

**Translation keys:**

```php
// lang/en/messages.php
return [
    'confirm_delete_user' => 'Delete User',
    'confirm_delete_user_description' => 'Are you sure you want to delete this user? This action cannot be undone.',
    'delete' => 'Delete',
];
```

## Best Practices

### ✅ DO

- Use `__()` for ALL labels, headings, and descriptions
- Extract reusable forms/tables into `Schemas/`
- Use Enums with `HasLabel` and `HasColor`
- Implement tenant scoping via `getEloquentQuery()`
- Use Policies for authorization
- Use `native(false)` for Select/DatePicker for better UX
- Use `columnSpanFull()` for full-width fields
- Use descriptive action names and icons
- Test all CRUD operations
- Use notifications for user feedback
- **Prevent self-deletion** in edit pages with `->hidden()`
- **Require confirmation** for destructive actions with `->requiresConfirmation()`

### ❌ DON'T

- Don't hardcode text strings (use translations)
- Don't skip tenant scoping in Owner panel
- Don't forget to authorize actions
- Don't use `protected static ?string $navigationLabel` (use method)
- Don't duplicate form/table schemas
- Don't forget to test Filament resources
- Don't use inline styles
- Don't skip validation
- Don't forget loading states
- **Don't allow self-deletion** without protection
- **Don't skip confirmation** on delete actions

## Quick Reference Checklist

Before finalizing ANY Filament resource:

- [ ] All text uses `__()`
- [ ] Tenant scoping implemented (`getEloquentQuery()`)
- [ ] Policy created and applied
- [ ] Enums implement `HasLabel` + `HasColor`
- [ ] Form schema extracted to `Schemas/`
- [ ] Table schema extracted to `Schemas/`
- [ ] Navigation label uses method (not property)
- [ ] Tests written for all CRUD operations
- [ ] Tests written for custom actions
- [ ] Mobile responsive (test on small screens)
- [ ] Dark mode support verified

## Common Patterns Summary

```php
// Resource structure
final class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationLabel(): string { return __('navigation.businesses'); }
    public static function form(Schema $schema): Schema { /* components */ }
    public static function table(Table $table): Table { /* schema */ }
    public static function getEloquentQuery(): Builder { /* tenant scoping */ }
}

// Form component
Forms\Components\TextInput::make('name')
    ->label(__('fields.name'))
    ->required();

// Table column
Tables\Columns\TextColumn::make('name')
    ->label(__('fields.name'))
    ->searchable()
    ->sortable();

// Action
Actions\Action::make('activate')
    ->label(__('messages.activate'))
    ->action(fn ($record) => $record->update(['is_active' => true]));

// Test
livewire(BusinessResource\Pages\ListBusinesses::class)
    ->assertCanSeeTableRecords($businesses);
```
