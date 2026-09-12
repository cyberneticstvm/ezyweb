<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'description', 'suitable_categories', 'preview_desktop_path', 'preview_mobile_path', 'default_theme', 'variant_map', 'is_active', 'sort_order'])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'suitable_categories' => 'array',
            'default_theme' => 'array',
            'variant_map' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
