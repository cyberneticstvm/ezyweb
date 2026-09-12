<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TemplateSeeder::class);
        $this->call(PlanSeeder::class);

        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (is_string($email) && $email !== '' && is_string($password) && $password !== '') {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => env('SUPER_ADMIN_NAME', 'Ezyweb Administrator'),
                    'password' => $password,
                    'platform_role' => PlatformRole::SuperAdmin,
                    'terms_accepted_at' => now(),
                ],
            );
        }
    }
}
