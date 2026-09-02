<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('email');
            $table->string('phone')->nullable()->after('company_name');
            $table->string('address')->nullable()->after('phone');
            $table->string('residency')->nullable()->after('address');
            $table->string('zip', 16)->nullable()->after('residency');
            $table->string('locale', 5)->default('en')->after('zip');
            $table->string('google_id')->nullable()->unique()->after('locale');
            $table->boolean('needs_role_selection')->default(false)->after('google_id');
            $table->boolean('is_blocked')->default(false)->after('needs_role_selection');
            $table->boolean('notify_by_email')->default(true)->after('is_blocked');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'phone', 'address', 'residency', 'zip', 'locale',
                'google_id', 'needs_role_selection', 'is_blocked', 'notify_by_email',
            ]);
        });
    }
};
