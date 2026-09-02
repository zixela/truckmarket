<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_number', 32)->nullable()->after('company_name');
            $table->string('company_phone', 40)->nullable()->after('company_number');
            $table->timestamp('company_verified_at')->nullable()->after('company_phone');
            $table->timestamp('phone_verified_at')->nullable()->after('company_verified_at');
        });

        Schema::table('verification_codes', function (Blueprint $table) {
            $table->string('channel', 10)->default('email')->after('user_id');
            $table->index(['user_id', 'channel', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_number', 'company_phone', 'company_verified_at', 'phone_verified_at']);
        });

        Schema::table('verification_codes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'channel', 'used_at']);
            $table->dropColumn('channel');
        });
    }
};
