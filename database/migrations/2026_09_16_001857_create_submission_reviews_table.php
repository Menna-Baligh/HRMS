<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create submission reviews table.
     */
    public function up(): void
    {
        Schema::create('submission_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('action');

            $table->text('feedback')->nullable();

            $table->timestamp('reviewed_at')
                ->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Drop submission reviews table.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_reviews');
    }
};