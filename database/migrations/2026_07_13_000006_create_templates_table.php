<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name');
            $table->text('description');
            $table->json('suitable_categories');
            $table->string('preview_desktop_path');
            $table->string('preview_mobile_path');
            $table->json('default_theme');
            $table->json('variant_map');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
