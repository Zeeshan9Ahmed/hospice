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
        Schema::create('cancelled_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('shift_id')->nullable()->references('id')->on('shifts')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('reason_id')->nullable()->references('id')->on('cancel_reasons')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancelled_shifts');
    }
};
