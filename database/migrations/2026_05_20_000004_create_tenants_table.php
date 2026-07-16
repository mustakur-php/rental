<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('type', 30)->index(); // individual, company
            $table->string('name');
            $table->string('mobile', 50)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index();

            // Individual fields
            $table->string('national_id', 80)->nullable()->index();
            $table->string('nationality', 80)->nullable();
            $table->date('birth_date')->nullable();

            // Company fields
            $table->string('company_name')->nullable();
            $table->string('commercial_registration', 120)->nullable()->index();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_mobile', 50)->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
