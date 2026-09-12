<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('title');
            $table->string('slug', 140);
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->string('icon')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('cta_label', 80)->nullable();
            $table->string('cta_url')->nullable();
            $this->visibility($table);
            $this->seo($table);
            $this->finish($table, 'services');
        });

        Schema::create('products', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('slug', 140);
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('show_price')->default(false);
            $table->string('enquiry_label', 80)->nullable();
            $this->visibility($table);
            $this->seo($table);
            $this->finish($table, 'products');
        });

        Schema::create('brands', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('website_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $this->finish($table, 'brands', false);
        });

        Schema::create('testimonials', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('client_name');
            $table->string('client_role')->nullable();
            $table->string('company')->nullable();
            $table->string('photo')->nullable();
            $table->text('quote');
            $table->unsignedTinyInteger('rating')->nullable();
            $this->visibility($table);
            $this->finish($table, 'testimonials');
        });

        Schema::create('portfolio_categories', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('slug', 140);
            $table->unsignedInteger('display_order')->default(0);
            $this->finish($table, 'portfolio_categories', false, true);
        });

        Schema::create('portfolio_projects', function (Blueprint $table): void {
            $this->workspace($table);
            $table->foreignId('portfolio_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug', 140);
            $table->string('client')->nullable();
            $table->date('project_date')->nullable();
            $table->string('location')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('case_study')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('project_url')->nullable();
            $this->visibility($table);
            $this->seo($table);
            $this->finish($table, 'portfolio_projects');
        });

        Schema::create('gallery_items', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('title');
            $table->string('image');
            $table->string('alt_text');
            $table->text('caption')->nullable();
            $table->string('category')->nullable();
            $table->string('aspect_ratio', 20)->default('auto');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $this->finish($table, 'gallery_items', false);
        });

        Schema::create('team_members', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('linkedin_url')->nullable();
            $this->visibility($table);
            $this->finish($table, 'team_members');
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $this->finish($table, 'faqs', false);
        });

        Schema::create('content_categories', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('slug', 140);
            $this->finish($table, 'content_categories', false, true);
        });

        Schema::create('content_tags', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('name');
            $table->string('slug', 140);
            $this->finish($table, 'content_tags', false, true);
        });

        Schema::create('content_posts', function (Blueprint $table): void {
            $this->workspace($table);
            $table->foreignId('content_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('content_type', 20);
            $table->string('title');
            $table->string('slug', 140);
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('author_name')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('open_graph_image')->nullable();
            $table->date('event_start_date')->nullable();
            $table->time('event_start_time')->nullable();
            $table->date('event_end_date')->nullable();
            $table->time('event_end_time')->nullable();
            $table->string('venue')->nullable();
            $table->text('address')->nullable();
            $table->string('map_url')->nullable();
            $table->string('registration_url')->nullable();
            $table->string('online_event_url')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['website_id', 'content_type', 'slug']);
            $table->index(['website_id', 'content_type', 'status', 'published_at'], 'content_posts_publication');
            $table->index(['tenant_id', 'website_id']);
        });

        Schema::create('content_post_tag', function (Blueprint $table): void {
            $table->foreignId('content_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_post_id', 'content_tag_id']);
        });

        Schema::create('job_openings', function (Blueprint $table): void {
            $this->workspace($table);
            $table->string('title');
            $table->string('slug', 140);
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type', 30)->nullable();
            $table->string('workplace_type', 30)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('salary_display')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('application_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $this->seo($table);
            $this->finish($table, 'job_openings', false);
        });

        Schema::create('job_applications', function (Blueprint $table): void {
            $this->workspace($table);
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('resume_path')->nullable();
            $table->boolean('consent')->default(false);
            $table->string('status', 30)->default('new');
            $table->timestamps();
            $table->index(['tenant_id', 'website_id', 'status', 'created_at'], 'job_applications_inbox');
        });
    }

    public function down(): void
    {
        foreach (['job_applications', 'job_openings', 'content_post_tag', 'content_posts', 'content_tags', 'content_categories', 'faqs', 'team_members', 'gallery_items', 'portfolio_projects', 'portfolio_categories', 'testimonials', 'brands', 'products', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function workspace(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('website_id')->constrained()->cascadeOnDelete();
    }

    private function visibility(Blueprint $table): void
    {
        $table->boolean('is_featured')->default(false);
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('display_order')->default(0);
    }

    private function seo(Blueprint $table): void
    {
        $table->string('seo_title')->nullable();
        $table->text('seo_description')->nullable();
    }

    private function finish(Blueprint $table, string $name, bool $softDeletes = true, bool $nameUnique = false): void
    {
        $table->timestamps();
        if ($softDeletes) {
            $table->softDeletes();
        }
        $table->index(['tenant_id', 'website_id']);
        if ($nameUnique) {
            $table->unique(['website_id', 'slug'], $name.'_website_slug_unique');
        } elseif (in_array($name, ['services', 'products', 'portfolio_projects', 'job_openings'], true)) {
            $table->unique(['website_id', 'slug'], $name.'_website_slug_unique');
        }
    }
};
