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
        Schema::create('aviator_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('aviator_rounds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('bet_key')->unique();
            $table->unsignedTinyInteger('slot')->default(1); // 1 = main, 2 = extra

            $table->decimal('amount', 16, 2);
            $table->string('status', 20)->default('pending')->index(); // pending, cashed_out, lost, cancelled

            $table->boolean('auto_cashout_enabled')->default(false);
            $table->decimal('auto_cashout_multiplier', 10, 2)->nullable();

            $table->decimal('cashout_multiplier', 10, 2)->nullable();
            $table->decimal('cashout_amount', 16, 2)->nullable();

            $table->timestamp('placed_at');
            $table->timestamp('cashed_out_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            $table->timestamps();

            $table->index(['round_id', 'user_id']);
            $table->index(['round_id', 'status']);
            $table->index(['round_id', 'user_id', 'slot', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_bets');
    }
};
