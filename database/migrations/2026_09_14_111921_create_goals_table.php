<?php

use App\Enums\GoalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2);
            $table->decimal('current_value', 10, 2)->default(0);
            $table->date('target_date');
            $table->string('status')->default(GoalStatus::ACTIVE->value);
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
