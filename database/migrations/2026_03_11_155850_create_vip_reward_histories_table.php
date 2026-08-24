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
        Schema::create('vip_reward_histories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vip_level_id')->constrained('vip_levels')->cascadeOnDelete();

            $table->string('reward_type', 50)->default('level_up');
            $table->decimal('reward_amount', 16, 2)->default(0);
            $table->decimal('bet_total', 16, 2)->default(0);
            $table->decimal('balance_before', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);

            $table->timestamp('rewarded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'vip_level_id', 'reward_type'],'uniq_user_vip_reward_type');
            $table->index(['user_id', 'rewarded_at'], 'idx_vrh_user_rewarded_at');
            $table->index(['vip_level_id', 'rewarded_at'], 'idx_vrh_vip_rewarded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_reward_histories');
    }
};
