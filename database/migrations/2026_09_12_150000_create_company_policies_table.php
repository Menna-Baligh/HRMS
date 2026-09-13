<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_code')->unique();
            $table->string('title');
            $table->string('category');
            $table->string('section')->nullable();
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_policies');
    }
};
