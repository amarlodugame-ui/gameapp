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
        Schema::create('rebate_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('tab')->default('all');
            $table->date('claim_date');

            $table->decimal('total_bet_amount', 16, 2)->default(0);
            $table->decimal('rebate_rate_percent', 8, 4)->default(0.0100);
            $table->decimal('rebate_amount', 16, 2)->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'claim_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebate_histories');
    }
};
