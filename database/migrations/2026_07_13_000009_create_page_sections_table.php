<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->unsignedBigInteger('draft_revision')->default(0)->after('initial_sections');
            $table->timestamp('draft_updated_at')->nullable()->after('draft_revision');
        });

        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('variant', 60);
            $table->string('anchor', 120)->nullable();
            $table->json('content');
            $table->json('design_settings');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_tablet')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'website_id', 'page_id', 'sort_order'], 'page_sections_workspace_order');
        });

        $this->backfillExistingWebsites();
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::table('websites', function (Blueprint $table): void {
            $table->dropColumn(['draft_revision', 'draft_updated_at']);
        });
    }

    private function backfillExistingWebsites(): void
    {
        DB::table('websites')->whereNull('deleted_at')->orderBy('id')->each(function (object $website): void {
            $now = now();
            $pageId = DB::table('pages')->insertGetId([
                'tenant_id' => $website->tenant_id,
                'website_id' => $website->id,
                'title' => 'Home',
                'slug' => 'home',
                'navigation_label' => 'Home',
                'is_home' => true,
                'is_enabled' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sections = json_decode((string) $website->initial_sections, true);
            foreach (is_array($sections) ? array_values($sections) : [] as $order => $type) {
                if (! is_string($type)) {
                    continue;
                }

                DB::table('page_sections')->insert([
                    'tenant_id' => $website->tenant_id,
                    'website_id' => $website->id,
                    'page_id' => $pageId,
                    'type' => $type,
                    'variant' => 'default',
                    'anchor' => Str::slug($type),
                    'content' => json_encode(['heading' => Str::headline($type), 'description' => 'Add your '.$type.' content here.'], JSON_THROW_ON_ERROR),
                    'design_settings' => json_encode(['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'], JSON_THROW_ON_ERROR),
                    'is_enabled' => true,
                    'show_on_desktop' => true,
                    'show_on_tablet' => true,
                    'show_on_mobile' => true,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }
};
