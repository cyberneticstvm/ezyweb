<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $definition) {
            $features = $definition['features'];
            unset($definition['features']);
            $plan = Plan::query()->updateOrCreate(['code' => $definition['code']], ['public_id' => Plan::query()->where('code', $definition['code'])->value('public_id') ?: (string) Str::ulid(), ...$definition]);
            foreach ($features as $key => [$value, $label]) {
                $plan->features()->updateOrCreate(['key' => $key], ['value' => $value, 'label' => $label]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function plans(): array
    {
        return [
            ['code' => 'starter', 'name' => 'Starter', 'description' => 'A focused landing website for a growing local business.', 'annual_price' => 4999, 'currency' => 'INR', 'trial_days' => 14, 'grace_days' => 7, 'retention_days' => 90, 'is_active' => true, 'is_featured' => false, 'sort_order' => 10, 'features' => [
                'websites_limit' => [1, 'One website'], 'pages_limit' => [5, 'Up to 5 pages'], 'services_limit' => [6, 'Up to 6 services'],
                'products_limit' => [20, 'Up to 20 products'], 'posts_limit' => [0, 'Legacy combined content limit'],
                'blog_posts_limit' => [0, 'Blog unavailable'], 'news_posts_limit' => [0, 'News unavailable'], 'events_limit' => [0, 'Events unavailable'], 'careers_limit' => [0, 'Careers unavailable'], 'storage_mb' => [250, '250 MB media storage'],
                'custom_domain_enabled' => [false, 'Platform subdomain'], 'remove_branding_enabled' => [false, 'Ezyweb branding'],
                'version_history_limit' => [10, '10 saved versions'], 'team_members_limit' => [1, 'One workspace member'], 'advanced_seo_enabled' => [false, 'Basic SEO'],
            ]],
            ['code' => 'business', 'name' => 'Business', 'description' => 'A complete multipage website with publishing and growth tools.', 'annual_price' => 11999, 'currency' => 'INR', 'trial_days' => 14, 'grace_days' => 15, 'retention_days' => 180, 'is_active' => true, 'is_featured' => true, 'sort_order' => 20, 'features' => [
                'websites_limit' => [1, 'One website'], 'pages_limit' => [50, 'Up to 50 pages'], 'services_limit' => [100, 'Up to 100 services'],
                'products_limit' => [500, 'Up to 500 products'], 'posts_limit' => [500, 'Legacy combined content limit'],
                'blog_posts_limit' => [500, 'Up to 500 blog posts'], 'news_posts_limit' => [500, 'Up to 500 news articles'], 'events_limit' => [250, 'Up to 250 events'], 'careers_limit' => [250, 'Up to 250 job openings'], 'storage_mb' => [2048, '2 GB media storage'],
                'custom_domain_enabled' => [true, 'Custom domain eligibility'], 'remove_branding_enabled' => [true, 'Remove Ezyweb branding'],
                'version_history_limit' => [50, '50 saved versions'], 'team_members_limit' => [10, 'Up to 10 workspace members'], 'advanced_seo_enabled' => [true, 'Advanced SEO'],
            ]],
        ];
    }
}
