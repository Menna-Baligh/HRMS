<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('id');
            $table->string('job_title')->nullable()->after('role');
            $table->enum('employment_type', ['Full-time', 'Part-time', 'Contract'])->nullable()->after('job_title');
            $table->date('start_date')->nullable()->after('employment_type');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('start_date');
            $table->text('address')->nullable()->after('phone');

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('status');
            $table->foreignId('company_location_id')->nullable()->constrained('company_locations')->nullOnDelete()->after('department_id');
            
            if (! Schema::hasColumn('users', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->after('company_location_id');
            }
        });

        DB::statement("
            UPDATE users u
            JOIN employees e ON u.id = e.user_id
            SET 
                u.employee_id = e.employee_id,
                u.job_title = e.job_title,
                u.employment_type = e.employment_type,
                u.start_date = e.start_date,
                u.status = e.status,
                u.department_id = e.department_id,
                u.company_location_id = e.company_location_id,
                u.address = e.address
        ");

        DB::statement("
            UPDATE users u
            JOIN employees e ON u.id = e.user_id
            JOIN employees m ON e.manager_id = m.id
            SET u.manager_id = m.user_id
            WHERE e.manager_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['company_location_id']);
            if (Schema::hasColumn('users', 'manager_id')) {
                $table->dropForeign(['manager_id']);
                $table->dropColumn('manager_id');
            }

            $table->dropColumn([
                'employee_id',
                'job_title',
                'employment_type',
                'start_date',
                'status',
                'address',
                'department_id',
                'company_location_id',
            ]);
        });
    }
};