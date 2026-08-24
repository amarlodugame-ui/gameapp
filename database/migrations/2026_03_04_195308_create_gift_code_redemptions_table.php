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
        Schema::create('gift_code_redemptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gift_code_id')->constrained('gift_codes')->cascadeOnDelete();
            $table->foreignId('user_id');
            $table->decimal('reward_amount',12,2)->default(0);
            $table->dateTime('redeemed_at');
            $table->timestamps();
            $table->unique(['gift_code_id','user_id']);
            $table->index('user_id');
            $table->index('gift_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_code_redemptions');
    }
};
