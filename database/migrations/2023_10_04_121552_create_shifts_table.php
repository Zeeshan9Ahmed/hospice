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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->nullable()->references('id')->on('patient_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['available', 'booked', 'completed']);
            $table->bigInteger('nurse_id')->nullable();
            $table->boolean('is_sheet_filled')->default(0);
            $table->boolean('is_platform_fee_paid')->default(0);
            $table->decimal('hours_worked')->nullable();
            $table->boolean('is_cancelled')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            // $table->decimal('hourly_rate')->nullable();
            // $table->decimal('total_amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
