<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_leases', function (Blueprint $table) {
            $table->string('contract_file_path', 500)->nullable()->after('lease_contract_number');
        });
    }

    public function down(): void
    {
        Schema::table('property_leases', function (Blueprint $table) {
            $table->dropColumn('contract_file_path');
        });
    }
};
