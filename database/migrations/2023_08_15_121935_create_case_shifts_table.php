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
        Schema::create('case_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->nullable()->references('id')->on('hospice_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->string('date');
            $table->string('time');
            $table->string('shift');
            $table->string('status');
            $table->bigInteger('nurse_id');
            $table->bigInteger('availability_id');
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
        Schema::dropIfExists('case_shifts');
    }
};
