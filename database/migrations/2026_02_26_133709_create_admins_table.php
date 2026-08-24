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
      Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('username', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('password', 191)->nullable();
            $table->string('image', 191)->nullable();
            $table->string('phone', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('last_login', 50)->nullable();
            $table->dateTime('last_seen')->nullable();
            $table->rememberToken(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
