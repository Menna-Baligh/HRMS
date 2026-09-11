<?php

use App\Enums\LeaveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(LeaveStatus::cases(), 'value'))
                ->default(LeaveStatus::Pending->value);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('requested_days', 5, 2);
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
