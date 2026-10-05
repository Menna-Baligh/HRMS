<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->text('description')->nullable();

            $table->string('price');

            $table->string('billing_period')->nullable();

            $table->boolean('is_popular')->default(false);

            $table->json('features');

            $table->string('button_text')->default('Get Started');

            $table->string('button_link')->nullable();

            $table->integer('order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_plans');
    }
};
