<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nurse_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('status');
            $table->string('day')->nullable();
            $table->date('date')->nullable();
            $table->string('start_shift_time');
            $table->string('end_shift_time');
            $table->foreignId('hospice_id')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('case_id')->nullable()->references('id')->on('hospice_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('shift_id')->nullable()->references('id')->on('hospice_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurse_availabilities');
    }
};
