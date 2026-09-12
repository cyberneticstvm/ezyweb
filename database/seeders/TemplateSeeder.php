<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('templates.catalog') as $index => $template) {
            Template::query()->updateOrCreate(
                ['key' => $index],
                [...$template, 'is_active' => true, 'sort_order' => array_search($index, array_keys(config('templates.catalog')), true) * 10],
            );
        }
    }
}
