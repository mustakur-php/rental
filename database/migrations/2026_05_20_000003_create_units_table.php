<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('type', 50)->index();
            $table->string('internal_number', 80)->nullable()->index();

            $table->decimal('area', 12, 2)->nullable();
            $table->string('floor', 80)->nullable();
            $table->string('electricity_meter', 120)->nullable()->index();
            $table->string('water_meter', 120)->nullable()->index();

            $table->text('description')->nullable();
            $table->string('status', 30)->default('vacant')->index();

            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['property_id', 'internal_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
