<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('platform_role')->default('user')->index()->after('password');
            $table->timestamp('terms_accepted_at')->nullable()->after('platform_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['platform_role']);
            $table->dropColumn(['phone', 'platform_role', 'terms_accepted_at']);
        });
    }
};
