<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // الشركة الأم — null تعني شركة رئيسية
            $table->foreignId('parent_id')->nullable()->after('id')
                  ->constrained('companies')->nullOnDelete();

            // نوع: main = رئيسية، subsidiary = فرعية
            $table->string('type', 30)->default('main')->after('parent_id');

            // بيانات قانونية
            $table->string('commercial_registration', 100)->nullable()->after('name');

            // بيانات التواصل
            $table->string('phone', 50)->nullable()->after('commercial_registration');
            $table->string('email', 255)->nullable()->after('phone');
            $table->string('address', 500)->nullable()->after('email');

            // الحساب البنكي
            $table->string('iban', 50)->nullable()->after('address');
            $table->string('bank_name', 150)->nullable()->after('iban');

            // الأرشيف
            $table->timestamp('archived_at')->nullable()->after('notes');
            $table->string('archived_reason', 100)->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id', 'type', 'commercial_registration',
                'phone', 'email', 'address',
                'iban', 'bank_name',
                'archived_at', 'archived_reason',
            ]);
        });
    }
};
