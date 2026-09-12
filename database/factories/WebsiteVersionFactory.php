<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Website;
use App\Models\WebsiteVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebsiteVersion> */
class WebsiteVersionFactory extends Factory
{
    protected $model = WebsiteVersion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'website_id' => Website::factory(),
            'version_number' => 1,
            'reason' => 'publish',
            'snapshot_json' => ['schema_version' => 1, 'website' => [], 'pages' => []],
            'created_by' => null,
            'published_at' => now(),
        ];
    }
}
