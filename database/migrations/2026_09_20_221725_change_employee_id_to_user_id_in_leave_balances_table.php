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
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);

            $table->dropUnique([
                'employee_id',
                'leave_type_id',
                'year',
            ]);

            $table->renameColumn(
                'employee_id',
                'user_id'
            );
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique([
                'user_id',
                'leave_type_id',
                'year',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->dropUnique([
                'user_id',
                'leave_type_id',
                'year',
            ]);

            $table->renameColumn(
                'user_id',
                'employee_id'
            );
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();

            $table->unique([
                'employee_id',
                'leave_type_id',
                'year',
            ]);
        });
    }
};