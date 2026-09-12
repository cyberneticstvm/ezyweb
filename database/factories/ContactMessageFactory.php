<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(), 'website_id' => Website::factory(),
            'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => fake()->phoneNumber(),
            'subject' => fake()->sentence(4), 'message' => fake()->paragraph(), 'source_page' => '/',
            'is_read' => false, 'is_archived' => false, 'submitted_at' => now(),
        ];
    }
}
