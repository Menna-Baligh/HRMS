<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change employee_id to user_id in submissions table.
     */
    public function up(): void
    {
        /*
         * The old employee foreign key was already dropped
         * before the previous migration failed.
         */

        // Keep the task foreign key valid while removing the old composite index.
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(
                'task_id',
                'submissions_task_id_temp_index'
            );
        });

        // Now the task_id foreign key has its own index.
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(
                'submissions_task_id_employee_id_index'
            );
        });

        // Remove the old employee_id column.
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });

        // Add the new user_id relationship.
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->after('task_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->index(['task_id', 'user_id']);
        });

        // Remove the temporary task_id index.
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(
                'submissions_task_id_temp_index'
            );
        });
    }

    /**
     * Revert user_id back to employee_id.
     */
    public function down(): void
    {
        // Remove the new composite index.
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(
                'submissions_task_id_user_id_index'
            );
        });

        // Remove user foreign key and column.
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Restore employee_id.
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->after('task_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->index(['task_id', 'employee_id']);
        });
    }
};