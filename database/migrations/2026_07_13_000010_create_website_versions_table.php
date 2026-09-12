<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('reason', 120);
            $table->json('snapshot_json');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['website_id', 'version_number']);
            $table->index(['tenant_id', 'website_id', 'published_at']);
        });

        Schema::table('websites', function (Blueprint $table): void {
            $table->string('publication_status', 30)->default('draft')->after('status')->index();
            $table->unsignedBigInteger('published_revision')->default(0)->after('draft_updated_at');
            $table->foreignId('published_version_id')->nullable()->after('published_revision')->constrained('website_versions')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->after('published_version_id')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('published_by');
            $table->text('last_publish_error')->nullable()->after('published_at');
        });

        $this->backfillPublishedWebsites();
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_version_id');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['publication_status', 'published_revision', 'published_at', 'last_publish_error']);
        });
        Schema::dropIfExists('website_versions');
    }

    private function backfillPublishedWebsites(): void
    {
        DB::table('websites')->where('status', 'published')->whereNull('deleted_at')->orderBy('id')->each(function (object $website): void {
            $pages = DB::table('pages')->where('website_id', $website->id)->whereNull('deleted_at')->orderBy('sort_order')->orderBy('id')->get();
            $snapshotPages = $pages->map(function (object $page) use ($website): array {
                $sections = DB::table('page_sections')->where('website_id', $website->id)->where('page_id', $page->id)
                    ->whereNull('deleted_at')->orderBy('sort_order')->orderBy('id')->get()->map(fn (object $section): array => [
                        'type' => $section->type,
                        'variant' => $section->variant,
                        'anchor' => $section->anchor,
                        'content' => json_decode((string) $section->content, true) ?: [],
                        'design_settings' => json_decode((string) $section->design_settings, true) ?: [],
                        'is_enabled' => (bool) $section->is_enabled,
                        'show_on_desktop' => (bool) $section->show_on_desktop,
                        'show_on_tablet' => (bool) $section->show_on_tablet,
                        'show_on_mobile' => (bool) $section->show_on_mobile,
                        'sort_order' => (int) $section->sort_order,
                    ])->all();

                return [
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'navigation_label' => $page->navigation_label,
                    'is_home' => (bool) $page->is_home,
                    'is_enabled' => (bool) $page->is_enabled,
                    'sort_order' => (int) $page->sort_order,
                    'sections' => $sections,
                ];
            })->all();

            $publishedAt = $website->updated_at ?? now();
            $versionId = DB::table('website_versions')->insertGetId([
                'tenant_id' => $website->tenant_id,
                'website_id' => $website->id,
                'version_number' => 1,
                'reason' => 'phase-6-backfill',
                'snapshot_json' => json_encode([
                    'schema_version' => 1,
                    'website' => [
                        'name' => $website->name,
                        'slug' => $website->slug,
                        'type' => $website->type,
                        'template_key' => $website->template_key,
                        'theme_settings' => json_decode((string) $website->theme_settings, true) ?: [],
                        'initial_sections' => json_decode((string) $website->initial_sections, true) ?: [],
                    ],
                    'settings' => [],
                    'navigation' => [],
                    'global_sections' => [],
                    'pages' => $snapshotPages,
                ], JSON_THROW_ON_ERROR),
                'created_by' => null,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);

            DB::table('websites')->where('id', $website->id)->update([
                'publication_status' => 'published',
                'published_revision' => $website->draft_revision,
                'published_version_id' => $versionId,
                'published_at' => $publishedAt,
            ]);
        });
    }
};
