<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('can access the application homepage', function (): void {
    get('/')
        ->assertOk()
        ->assertViewIs('welcome');
});

it('can access application with valid routes', function (): void {
    get('/')
        ->assertOk()
        ->assertViewIs('welcome');
});

it('handles non-existent routes with 404', function (): void {
    get('/definitely-does-not-exist')
        ->assertNotFound();
});

it('returns 404 for non-existent routes', function (): void {
    get('/non-existent-route')
        ->assertNotFound();
});
