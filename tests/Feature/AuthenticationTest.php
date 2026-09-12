<?php

use App\Enums\PlatformRole;
use App\Models\User;

it('registers and authenticates a new user', function () {
    $response = $this->post('/register', [
        'name' => 'Ezyweb Customer',
        'email' => 'customer@example.com',
        'phone' => '+91 98765 43210',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'terms' => true,
    ]);

    $response->assertRedirect('/onboarding');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'customer@example.com']);
});

it('authenticates an existing user', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('sends a super administrator directly to the platform admin area', function () {
    $admin = User::factory()->create(['password' => 'password', 'platform_role' => PlatformRole::SuperAdmin]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('logs an authenticated user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
