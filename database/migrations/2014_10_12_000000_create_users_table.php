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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('otp')->nullable();
            $table->string('role');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('license_no')->nullable();
            $table->string('discipline')->nullable();
            $table->string('rates')->nullable();
            $table->string('fax')->nullable();
            $table->string('password')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('card_id')->nullable();
            $table->integer("is_card")->default(0);
            $table->string('customer_id')->nullable();
            $table->integer("account_verified")->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_token')->nullable();
            $table->string('is_social')->default(0);
            $table->string('is_forgot')->default(0);
            $table->string('user_social_token')->nullable();
            $table->string('user_social_type')->nullable();
            $table->string('is_profile_complete')->nullable();
            $table->string('is_blocked')->nullable()->default('0');
            $table->integer('subscription_id')->nullable();
            $table->string('api_token')->nullable();
            $table->boolean('is_approved')->default('0');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
