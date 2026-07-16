<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 50)->index();
            $table->string('city', 100)->nullable()->index();
            $table->string('district', 100)->nullable()->index();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active')->index();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['city', 'district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
