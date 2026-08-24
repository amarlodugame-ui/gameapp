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
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();

            $table->string('period_id', 30)->unique();

            $table->enum('game_type', ['30s','1m','3m','5m'])->index();
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time')->index();

            $table->unsignedTinyInteger('result_number')->nullable();
            $table->enum('result_color', ['red','green','violet'])->nullable();
            $table->enum('result_bigsmall', ['big','small'])->nullable();

            $table->enum('status', ['queued','running','ended'])->default('running')->index();

            $table->timestamps();

            $table->index(['game_type','status','end_time'], 'gr_type_status_end');
            $table->index(['game_type','status','id'], 'gr_type_status_id');
            $table->index(['game_type','created_at','id'], 'gr_type_created_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
