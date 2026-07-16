<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_maps', function (Blueprint $table) {
            // floor_plan = مخطط معماري | satellite = صورة جوية
            $table->string('map_type', 30)->default('floor_plan')->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('property_maps', function (Blueprint $table) {
            $table->dropColumn('map_type');
        });
    }
};
