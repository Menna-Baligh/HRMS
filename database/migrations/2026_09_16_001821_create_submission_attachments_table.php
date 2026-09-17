<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create submission attachments table.
     */
    public function up(): void
    {
        Schema::create('submission_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');

            $table->timestamps();
        });
    }

    /**
     * Drop submission attachments table.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_attachments');
    }
};
