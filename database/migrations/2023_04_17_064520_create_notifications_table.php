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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->integer('sender_id');
            $table->integer('receiver_id');
            $table->integer('shift_id');
            $table->integer('request_id');
            $table->integer('post_id')->nullable();
            $table->integer('case_id')->nullable();
            $table->integer('nurse_id')->nullable();
            $table->string('type');
            $table->string('message');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
