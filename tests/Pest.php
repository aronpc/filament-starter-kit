<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\User;
use Illuminate\Support\Sleep;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function (): void {
        Illuminate\Support\Str::createRandomStringsNormally();
        Illuminate\Support\Str::createUuidsNormally();
        Illuminate\Support\Facades\Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user and authenticate them.
 */
function login(?User $user = null): User
{
    $user ??= User::factory()->create();

    actingAs($user);

    return $user;
}

/**
 * Create an admin user with super_admin role and authenticate them.
 */
function loginAsAdmin(): User
{
    $user = User::factory()->admin()->create();

    actingAs($user);

    return $user;
}

/**
 * Create a user with specific role and authenticate them.
 */
function loginAsRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    actingAs($user);

    return $user;
}

/**
 * Assert that the database has a record with the given attributes.
 */
function assertDatabaseHasRecord(string $table, array $attributes): void
{
    assertDatabaseHas($table, $attributes);
}

/**
 * Assert that the database does not have a record with the given attributes.
 */
function assertDatabaseMissingRecord(string $table, array $attributes): void
{
    assertDatabaseMissing($table, $attributes);
}

/**
 * Assert that the database has a specific count of records.
 */
function assertDatabaseCountRecords(string $table, int $count): void
{
    assertDatabaseCount($table, $count);
}

/**
 * Create model with factory and optional state.
 */
function create(string $model, array $attributes = [], int $count = 1)
{
    $factory = $model::factory();

    if ($attributes !== []) {
        $factory = $factory->state($attributes);
    }

    return $count === 1 ? $factory->create() : $factory->count($count)->create();
}

/**
 * Create multiple records of the same model.
 */
function createMany(string $model, int $count, array $attributes = [])
{
    return create($model, $attributes, $count);
}
