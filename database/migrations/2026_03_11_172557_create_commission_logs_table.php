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
        Schema::create('commission_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('to_id')->nullable()->comment('Receiver user id: the actual user who gets the commission');
            $table->unsignedBigInteger('from_id')->nullable()->comment('Source user id: the user whose action generated the commission');

            $table->string('level', 191);
            $table->decimal('amount', 11, 2);
            $table->string('type', 40)->nullable();
            $table->string('details', 255)->nullable();
            $table->string('trx', 191);
            $table->timestamps();

            $table->foreign('to_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('from_id')->references('id')->on('users')->nullOnDelete();

            $table->index('to_id');
            $table->index('from_id');
            $table->index('trx');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_logs');
    }
};
