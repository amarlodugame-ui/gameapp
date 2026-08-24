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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('withdraw_wallet_id')->nullable()->index();

            $table->string('payout_currency_code', 50)->nullable()->index();
            $table->decimal('amount', 18, 8)->default(0);
            $table->string('number', 50);
            $table->decimal('charge', 18, 8)->default(0);
            $table->decimal('net_amount', 18, 8)->default(0);

            $table->string('response_id', 100)->nullable()->index();
            $table->string('last_error', 255)->nullable();
            $table->text('information')->nullable();
            $table->text('meta_field')->nullable();
            $table->text('feedback')->nullable();
            $table->string('trx_id', 50)->nullable()->index();

            $table->unsignedTinyInteger('status')->default(0)->comment('0=> pending, 1=> processing, 2=> completed, 3=> rejected')->index();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
