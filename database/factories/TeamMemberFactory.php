<?php

namespace Database\Factories;

use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'name' => fake()->name(), 'role' => fake()->jobTitle(), 'bio' => fake()->paragraph(), 'email' => fake()->safeEmail(), 'is_active' => true, 'is_featured' => false, 'display_order' => 0];
    }
}
