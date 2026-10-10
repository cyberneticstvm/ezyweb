<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contact_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('heading')->default('Contact us');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->json('phone_numbers')->nullable();
            $table->json('email_addresses')->nullable();
            $table->text('working_hours')->nullable();
            $table->string('maps_embed_url')->nullable();
            $table->json('form_fields');
            $table->string('success_message')->default('Thank you. We will get back to you soon.');
            $table->string('recipient_email')->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->boolean('is_form_enabled')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'website_id']);
        });

        Schema::create('website_whatsapp_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('phone_number', 30)->nullable();
            $table->string('default_message', 500)->default('Hello, I visited your website and would like more information.');
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_tablet')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->string('position', 10)->default('right');
            $table->unsignedSmallInteger('bottom_offset')->default(24);
            $table->unsignedSmallInteger('display_delay')->default(0);
            $table->boolean('pulse_animation')->default(true);
            $table->boolean('show_label')->default(true);
            $table->json('included_pages')->nullable();
            $table->boolean('mobile_sticky_bar')->default(false);
            $table->string('call_number', 30)->nullable();
            $table->string('directions_url')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'website_id']);
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('source_page')->nullable();
            $table->text('referrer')->nullable();
            $table->json('utm_parameters')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            // DATETIME avoids legacy MySQL/MariaDB implicit TIMESTAMP defaults
            // (for example 0000-00-00 00:00:00 under strict SQL mode).
            $table->dateTime('submitted_at');
            $table->timestamps();
            $table->index(['tenant_id', 'website_id', 'is_archived', 'is_read', 'submitted_at'], 'contact_messages_inbox');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('website_whatsapp_settings');
        Schema::dropIfExists('website_contact_settings');
    }
};
