<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_request_id')
                ->constrained('leave_requests')
                ->cascadeOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('previous_status');

            $table->string('decision');

            $table->text('reason')->nullable();

            $table->timestamp('decided_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_decisions');
    }
};
