<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            // Leave type name
            $table->string('name')->unique();
            // Optional description
            $table->text('description')->nullable();
            // Only active leave types can be used in leave requests
            $table->boolean('is_active')->default(true);
            // Determines whether this leave type requires a balance
            $table->boolean('requires_balance')->default(true);
            // Determines whether an attachment is required
            $table->boolean('requires_attachment')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
