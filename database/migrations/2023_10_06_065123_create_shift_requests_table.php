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
        Schema::create('shift_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('availability_id')->nullable()->references('id')->on('nurse_availabilities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('hospice_id')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('case_id')->nullable()->references('id')->on('patient_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('shift_id')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled', 'completed'])->default('pending');
            $table->boolean('sent_by_nurse');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_requests');
    }
};
