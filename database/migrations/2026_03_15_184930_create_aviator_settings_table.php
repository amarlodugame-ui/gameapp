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
        Schema::create('aviator_settings', function (Blueprint $table) {
            $table->id();
            // Operational toggles
            $table->boolean('enabled')->default(true);
            // Your requested 4s intro + 4s result windows
            $table->unsignedInteger('betting_duration_ms')->default(4000);
            $table->unsignedInteger('result_duration_ms')->default(4000);
            // Risk controls (non-rigging, disclosed controls)
            $table->unsignedInteger('max_bet_amount')->default(100000); //adjust for your currency

            $table->unsignedInteger('max_payout_amount')->default(5000000); //exposure cap 
            // Optional: transparent house-edge control (basis points: 100 = 1.00%)

            $table->unsignedInteger('house_edge_bps')->default(100);
            // Audio toggle
            $table->boolean('sound_enabled_by_default')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_settings');
    }
};
