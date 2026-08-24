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
        Schema::create('vip_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('vip')->unique();
            $table->string('level_class');
            $table->string('right_img');
            $table->string('head_img');
            $table->unsignedBigInteger('require_exp');
            $table->string('progress_bg', 50);

            $table->unsignedBigInteger('level_up_reward_money')->default(0);
            $table->unsignedBigInteger('level_up_reward_love')->default(0);

            $table->unsignedBigInteger('monthly_reward_money')->default(0);
            $table->unsignedBigInteger('monthly_reward_love')->default(0);

            $table->decimal('safe_rate', 5, 2)->default(0.00);
            $table->decimal('rebate_rate', 5, 2)->default(0.00);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_levels');
    }
};
