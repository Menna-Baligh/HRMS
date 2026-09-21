<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new user_id column temporarily.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        // Convert existing employee_id values to their related user_id.
        DB::statement('
            UPDATE leave_requests
            INNER JOIN employees
                ON employees.id = leave_requests.employee_id
            SET leave_requests.user_id = employees.user_id
        ');

        // Make user_id required after migration of existing data.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Remove the old employee relationship.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('id');
        });

        // Convert user_id back to employee_id where possible.
        DB::statement('
            UPDATE leave_requests
            INNER JOIN employees
                ON employees.user_id = leave_requests.user_id
            SET leave_requests.employee_id = employees.id
        ');

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
