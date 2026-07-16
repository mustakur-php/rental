<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['properties', 'units', 'tenants', 'contracts', 'property_leases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->index();
                $table->string('archived_reason')->nullable();
                $table->text('archived_notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['properties', 'units', 'tenants', 'contracts', 'property_leases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['archived_at', 'archived_reason', 'archived_notes']);
            });
        }
    }
};
