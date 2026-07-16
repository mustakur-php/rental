<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();

            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date')->index();

            $table->decimal('base_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);

            $table->unsignedSmallInteger('grace_period_days')->default(10);
            $table->date('paid_at')->nullable();

            $table->string('status', 30)->default('pending')->index();
            $table->timestamps();

            $table->unique(['contract_id', 'installment_no']);
            $table->index(['contract_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
