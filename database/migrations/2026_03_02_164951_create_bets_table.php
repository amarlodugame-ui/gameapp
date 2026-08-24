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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('round_id')->constrained('game_rounds')->cascadeOnDelete();

            $table->enum('bet_type', ['color','number','bigsmall']);
            $table->string('bet_value', 10);

            $table->decimal('amount', 16, 2);
            $table->decimal('multiplier', 8, 2);
            $table->unsignedInteger('qty')->default(1);

            $table->decimal('payout', 16, 2)->default(0.00);

            $table->enum('status', ['pending','win','lose'])->default('pending')->index();
            $table->string('game_type', 10)->nullable()->index();
            $table->timestamps();

            $table->index(['round_id','status'], 'bets_round_status');
            $table->index(['user_id','round_id'], 'bets_user_round');
            $table->index(['user_id','round_id','status'], 'bets_user_round_status');
            $table->index(['user_id','game_type','id'], 'bets_user_game_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
