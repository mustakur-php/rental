<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->string('code', 80)->unique();

            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('priority', 30)->default('medium')->index();
            $table->string('status', 30)->default('new')->index();

            $table->date('request_date')->index();
            $table->date('completed_date')->nullable();

            $table->decimal('cost', 15, 2)->default(0);
            $table->string('unit_impact', 50)->default('none')->index(); // none, affects_operation, stops_unit

            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
