<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->morphs('attachable');

            $table->string('code', 80)->nullable()->unique();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->string('file_type', 50)->nullable()->index(); // image, pdf, excel, document, video, other
            $table->string('category', 80)->nullable()->index(); // contract, identity, transfer, maintenance, map
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
