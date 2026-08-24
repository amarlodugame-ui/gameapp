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
        Schema::create('daily_task_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('task_key', 100);
            $table->string('task_title', 150);
            $table->string('task_group', 30)->default('daily');
            $table->decimal('required_amount', 16, 2)->default(0);
            $table->decimal('reward_amount', 16, 2)->default(0);
            $table->decimal('completed_amount', 16, 2)->default(0);
            $table->date('qualified_date')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'task_key']);
            $table->index(['user_id', 'task_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_task_claims');
    }
};
