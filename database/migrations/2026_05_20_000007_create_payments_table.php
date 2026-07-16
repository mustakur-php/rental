<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('code', 80)->unique();

            $table->foreignId('contract_id')->constrained('contracts')->restrictOnDelete();
            $table->foreignId('payment_schedule_id')->constrained('payment_schedules')->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();

            $table->decimal('amount', 15, 2);
            $table->date('payment_date')->index();

            $table->string('method', 50)->index(); // bank_transfer, cash, cheque, other
            $table->string('reference_number')->nullable()->index();
            $table->text('notes')->nullable();

            $table->string('status', 30)->default('registered')->index();

            $table->timestamps();

            $table->index(['tenant_id', 'payment_date']);
            $table->index(['property_id', 'payment_date']);
            $table->index(['unit_id', 'payment_date']);
            $table->index(['payment_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
