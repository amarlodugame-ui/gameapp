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
        Schema::create('strong_box_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['in','out','interest']);
            $table->enum('actor', ['user','system','admin'])->default('user');
            $table->decimal('amount',16,2);
            $table->decimal('balance_after',16,2)->default(0);
            $table->timestamp('interest_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strong_box_transactions');
    }
};
