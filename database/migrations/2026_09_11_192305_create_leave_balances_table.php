<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();

            // Employee who owns this leave balance
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Leave type associated with this balance
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            // Balance year
            $table->smallInteger('year');
            // Total allocated leave days
            $table->decimal('allocated_days', 8, 2)->default(0);
            // Total used leave days
            $table->decimal('used_days', 8, 2)->default(0);
            $table->timestamps();
            // One balance per employee, leave type and year
            $table->unique([
                'employee_id',
                'leave_type_id',
                'year',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
