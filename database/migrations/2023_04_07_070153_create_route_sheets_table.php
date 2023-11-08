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
        Schema::create('route_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('case_id')->references('id')->on('hospice_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('shift_id')->nullable();
            $table->string('date');
            $table->string('staff_name');
            $table->string('patient_name');
            $table->string('signature');
            $table->string('service_code');
            $table->string('time_in');
            $table->string('time_out');
            $table->string('hours_worked');
            $table->string('hourly_rate');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_sheets');
    }
};
