<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->morphs('notifiable_source');

            $table->string('type', 80)->index(); // payment_due, payment_overdue, contract_expiring, unit_vacant
            $table->string('severity', 30)->default('info')->index(); // info, warning, danger, success
            $table->string('title');
            $table->text('message')->nullable();

            $table->date('trigger_date')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->string('status', 30)->default('open')->index(); // open, resolved, dismissed

            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
