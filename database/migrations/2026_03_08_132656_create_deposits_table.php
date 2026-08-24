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
        Schema::create('deposits', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('payment_method_currency', 50)->nullable();
            // monetary fields
            $table->decimal('amount', 18, 8)->default(0)->comment('in base currency');
            $table->decimal('charge', 18, 8)->default(0);
            $table->decimal('payable_amount', 18, 8)->default(0)->comment('Amount paid');

            $table->string('payment_id', 100)->nullable();
            $table->string('trx_id', 100)->nullable();

            $table->text('information')->nullable();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('0=pending, 1=success, 2=awaiting payment, 3=rejected');
            $table->timestamps();

            $table->index('user_id', 'deposits_user_id_index');
            $table->index('status', 'deposits_status_index');
            $table->index('created_at', 'deposits_created_at_index');

            $table->index(['user_id', 'status'], 'deposits_user_status_index');
            $table->index(['status', 'created_at'], 'deposits_status_created_at_index');

            $table->index('payment_id', 'deposits_payment_id_index');
            $table->index('trx_id', 'deposits_trx_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
