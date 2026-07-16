<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أضف ownership_type للعقارات
        Schema::table('properties', function (Blueprint $table) {
            $table->string('ownership_type', 20)->default('owned')->after('status'); // owned / leased
        });

        // جدول عقود إيجار العقارات من الملاك
        Schema::create('property_leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('owner_name')->comment('اسم المالك');
            $table->string('owner_mobile', 50)->nullable();
            $table->string('owner_iban', 50)->nullable();
            $table->string('lease_contract_number', 100)->nullable()->comment('رقم عقد إيجار');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_amount', 15, 2)->comment('إجمالي قيمة الإيجار');
            $table->string('payment_cycle', 30)->default('monthly');
            $table->unsignedSmallInteger('installments_count')->default(12);
            $table->string('status', 30)->default('active'); // active, ended, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // جداول دفع عقود الملاك
        Schema::create('property_lease_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_lease_id')->constrained('property_leases')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->string('status', 30)->default('pending'); // pending, paid, overdue, partial
            $table->date('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_number', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_lease_schedules');
        Schema::dropIfExists('property_leases');
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('ownership_type');
        });
    }
};
