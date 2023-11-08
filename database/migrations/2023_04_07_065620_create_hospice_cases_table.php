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
        Schema::create('hospice_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('patient_name')->nullable();
            $table->string('location')->nullable();
            $table->string('dob')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('gender')->nullable();
            $table->string('discipline_needed')->nullable();
            $table->string('care_level')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('case_status')->nullable();
            $table->string('open_shift')->nullable();
            $table->string('close_shift')->nullable();
            $table->string('note')->nullable();
            $table->string('status')->default('available')->nullable();
            $table->foreignId('nurse_id')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_sheet_filled')->default(0);
            $table->boolean('is_platform_fee_paid')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospice_cases');
    }
};
