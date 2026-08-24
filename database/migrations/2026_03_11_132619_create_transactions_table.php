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
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('user_id')->default(0);

            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('post_balance', 28, 8)->default(0);

            $table->string('trx_type', 40)->nullable();
            $table->string('trx', 40)->nullable();
            $table->string('details', 255)->nullable();
            $table->string('remark', 40)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'id'], 'idx_transactions_user_id_id');
            $table->index('trx', 'idx_transactions_trx');
            $table->unique('trx', 'uniq_transactions_trx');
            $table->index(['remark', 'id'], 'idx_transactions_remark_id');
            $table->index('created_at', 'idx_transactions_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
