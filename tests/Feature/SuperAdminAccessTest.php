<?php

use App\Enums\PlatformRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('forbids regular users from the super admin area', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('allows super administrators to access the platform area', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/index'));
});

it('does not force super administrators through tenant onboarding', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);

    $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
    $this->actingAs($admin)->get('/onboarding')->assertRedirect('/admin');
});
