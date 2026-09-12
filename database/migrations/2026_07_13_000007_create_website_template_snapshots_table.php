<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_template_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_template_key', 40);
            $table->string('to_template_key', 40);
            $table->json('theme_settings');
            $table->json('initial_sections');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'website_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_template_snapshots');
    }
};
