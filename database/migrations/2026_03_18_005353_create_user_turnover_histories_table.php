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
        Schema::create('user_turnover_histories', function (Blueprint $table) {
             $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('old_turnover_required', 20, 2)->default(0);
            $table->decimal('change_amount', 20, 2)->default(0);
            $table->enum('type', ['add', 'subtract']);
            $table->decimal('new_turnover_required', 20, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_turnover_histories');
    }
};
