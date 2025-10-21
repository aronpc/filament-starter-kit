<?php

declare(strict_types=1);

it('ensures application is in testing environment (APP_ENV)', function (): void {
    expect(app()->environment())->toBe('testing');
})->group('config');

it('uses sqlite in-memory database for tests (DB_CONNECTION|DB_DATABASE)', function (): void {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database', null))->toBe(':memory:');
})->group('config');

it('uses array drivers for cache during tests (CACHE_STORE)', function (): void {
    expect(config('cache.default'))->toBe('array');
})->group('config');

it('uses array drivers for session during tests (SESSION_DRIVER)', function (): void {
    expect(config('session.driver'))->toBe('array');
})->group('config');

it('uses sync driver for queue during tests (QUEUE_CONNECTION)', function (): void {
    expect(config('queue.default'))->toBe('sync');
})->group('config');
