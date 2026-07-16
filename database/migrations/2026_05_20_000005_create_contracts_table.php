<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();

            $table->foreignId('previous_contract_id')->nullable()->constrained('contracts')->nullOnDelete();

            $table->string('code', 80)->unique();

            $table->date('start_date')->index();
            $table->date('end_date')->index();

            $table->decimal('total_contract_amount', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_with_vat', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('SAR');

            $table->string('payment_cycle', 50)->index(); // monthly, bimonthly, quarterly, semi_annual, annual, custom
            $table->unsignedSmallInteger('installments_count')->nullable();

            $table->string('status', 30)->default('draft')->index();

            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('termination_notes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // One active contract per unit.
        // MySQL does not support partial indexes, so enforce in service layer.
        // If using PostgreSQL, add a partial unique index manually:
        // CREATE UNIQUE INDEX contracts_one_active_per_unit ON contracts(unit_id) WHERE status = 'active';
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
