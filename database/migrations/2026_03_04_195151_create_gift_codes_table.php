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
        Schema::create('gift_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code',60)->unique();

            $table->decimal('reward_amount',12,2)->default(0);
            $table->unsignedInteger('max_users')->default(1);
            $table->unsignedInteger('used_count')->default(0);

            $table->boolean('is_used')->default(0);
            $table->dateTime('last_used_at')->nullable();

            $table->timestamps();

            $table->index('is_used');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_codes');
    }
};
