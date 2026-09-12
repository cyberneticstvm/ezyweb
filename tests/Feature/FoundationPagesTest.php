<?php

use App\Models\TenantMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('loads the public homepage', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('home'));
});

it('loads the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

it('loads the registration page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
});

it('redirects unauthenticated users away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('allows an authenticated user to access the dashboard', function () {
    $user = User::factory()->create();
    TenantMembership::factory()->create(['user_id' => $user->getKey()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/index')
            ->where('auth.user.email', $user->email));
});
