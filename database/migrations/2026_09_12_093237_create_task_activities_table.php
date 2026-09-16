<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            // User who performed the activity
             $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Activity type
            $table->enum('action', ['created','updated','assigned','unassigned','status_changed','progress_updated',]);
            // Previous value before the change
              $table->text('old_value')->nullable();
            // New value after the change
            $table->text('new_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
