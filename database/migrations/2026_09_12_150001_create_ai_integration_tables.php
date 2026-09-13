<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('feature');
            $table->unsignedInteger('version')->default(1);
            $table->string('context_reference')->nullable();
            $table->foreignId('regenerated_from_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->json('request_envelope')->nullable();
            $table->json('output_payload')->nullable();
            $table->string('status')->default('success');
            $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature');
            $table->string('status');
            $table->timestamp('request_time')->useCurrent();
            $table->unsignedInteger('response_duration_ms')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ai_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_generation_id')->constrained('ai_generations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rating', ['useful', 'not_useful', 'flagged']);
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedbacks');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_generations');
    }
};
