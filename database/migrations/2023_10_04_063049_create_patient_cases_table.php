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
        Schema::create('patient_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('patient_name')->nullable();
            $table->string('location')->nullable();
            $table->string('dob')->nullable();
            $table->string('phone_number')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('discipline', ['RN', 'LVN', 'HHA'])->nullable();
            $table->enum('care_level', ['routine care', 'continuous care', 'inpatient care', 'respite care'])->nullable();
            $table->enum('case_status', ['PRN', 'Ongoing'])->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_completed')->default(0);
            $table->boolean('is_patient_died')->default(0);
            $table->boolean('patient_case_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_cases');
    }
};
