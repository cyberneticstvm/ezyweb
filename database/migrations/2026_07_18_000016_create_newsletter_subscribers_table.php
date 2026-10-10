<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('source_page')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->dateTime('consented_at');
            $table->dateTime('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->unique(['website_id', 'email']);
            $table->index(['tenant_id', 'website_id', 'unsubscribed_at', 'subscribed_at'], 'newsletter_subscribers_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
