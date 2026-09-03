<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            // Set when the recipient likes the message (two-party chat, so one flag is enough).
            $table->timestamp('liked_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropColumn('liked_at');
        });
    }
};
