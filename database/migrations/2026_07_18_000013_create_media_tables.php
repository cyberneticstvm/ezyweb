<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40);
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('source_mime_type', 100);
            $table->string('mime_type', 100)->default('image/webp');
            $table->string('extension', 10)->default('webp');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('total_size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('folder')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->json('variants');
            $table->char('checksum', 64);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'website_id', 'folder', 'created_at'], 'media_assets_library');
        });

        Schema::create('media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 100);
            $table->timestamps();
            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'field'], 'media_usage_unique');
            $table->index(['usable_type', 'usable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_assets');
    }
};
