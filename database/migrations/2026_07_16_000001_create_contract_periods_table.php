<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_no');
            $table->unsignedSmallInteger('duration_months');
            $table->decimal('annual_amount', 15, 2);
            $table->decimal('increase_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_periods');
    }
};
