<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // التنبيه مؤجَّل حتى هذا الوقت؛ بعده يعود للظهور إن بقي سببه قائماً.
            $table->timestamp('snoozed_until')->nullable()->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('snoozed_until');
        });
    }
};
