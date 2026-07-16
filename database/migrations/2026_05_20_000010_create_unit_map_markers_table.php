<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_map_markers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_map_id')->constrained('property_maps')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();

            $table->string('label', 50)->nullable();

            // Store as percentage from 0 to 100 so the map stays responsive.
            $table->decimal('x_coordinate', 8, 4);
            $table->decimal('y_coordinate', 8, 4);

            $table->timestamps();

            $table->unique(['property_map_id', 'unit_id']);
            $table->index(['unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_map_markers');
    }
};
