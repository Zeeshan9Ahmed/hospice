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
        Schema::create('case_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospice_id')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('case_id')->nullable()->references('id')->on('hospice_cases')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('shift_id')->nullable();
            $table->foreignId('nurse_id')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_approvals');
    }
};
