<?php

use App\Enums\EvaluationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('evaluation_periods')->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default(EvaluationStatus::DRAFT->value);
            $table->timestamps();

            $table->index(['user_id', 'period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
