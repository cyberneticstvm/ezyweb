<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('platform_name')->default('Ezyweb');
            $table->string('support_email')->nullable();
            $table->boolean('registrations_enabled')->default(true);
            $table->char('default_country_code', 2)->default('IN');
            $table->string('default_timezone', 64)->default('Asia/Kolkata');
            $table->string('default_locale', 10)->default('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
