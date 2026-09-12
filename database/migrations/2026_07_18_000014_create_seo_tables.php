<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('site_title')->nullable();
            $table->text('default_meta_description')->nullable();
            $table->string('default_social_image')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_type', 40)->default('Organization');
            $table->string('primary_language', 15)->default('en');
            $table->boolean('search_engine_visibility')->default(true);
            $table->string('google_site_verification')->nullable();
            $table->string('google_analytics_id', 30)->nullable();
            $table->json('social_profiles')->nullable();
            $table->string('not_found_title')->default('Page not found');
            $table->text('not_found_message')->nullable();
            $table->string('not_found_button_label')->default('Return home');
            $table->timestamps();
            $table->index(['tenant_id', 'website_id']);
        });

        Schema::create('page_seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('open_graph_title')->nullable();
            $table->text('open_graph_description')->nullable();
            $table->string('open_graph_image')->nullable();
            $table->boolean('is_indexed')->default(true);
            $table->boolean('is_followed')->default(true);
            $table->string('schema_type', 40)->default('WebPage');
            $table->timestamps();
            $table->index(['tenant_id', 'website_id', 'page_id']);
        });

        Schema::create('redirects', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('source_path', 500);
            $table->string('target_path', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            $table->unique(['website_id', 'source_path']);
            $table->index(['tenant_id', 'website_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('page_seo_settings');
        Schema::dropIfExists('website_seo_settings');
    }
};
