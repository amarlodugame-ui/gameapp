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
        Schema::create('game_controls', function (Blueprint $table) {
            $table->id();
            $table->enum('game_type', ['30s', '1m', '3m', '5m']);
            $table->enum('mode', ['random', 'profit_control', 'manual'])->default('random');
            $table->unsignedTinyInteger('manual_number')->nullable();
            $table->decimal('profit_margin', 5, 2)->default(0.00);
            $table->timestamps();
            $table->unique('game_type', 'uq_game_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_controls');
    }
};
