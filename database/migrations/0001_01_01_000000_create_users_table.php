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
        Schema::create('users', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->string('username')->unique()->nullable();
            $table->string('email')->unique()->nullable();

            $table->string('phone_code', 10)->nullable();
            $table->string('phone', 20)->unique();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('game_balance', 15, 2)->default(0.00);

            $table->decimal('turnover_required', 15, 2)->default(0.00);
            $table->decimal('turnover_completed', 15, 2)->default(0.00);

            $table->string('referral_code')->unique()->nullable();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('image')->nullable();

            $table->enum('role', ['demo', 'user'])->default('user')->comment('demo = test account, user = real account');
            $table->boolean('status')->default(1)->comment('1 = active, 0 = blocked');

            $table->rememberToken();
            $table->timestamps();

            $table->index(['phone', 'email']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');      
    }
};
