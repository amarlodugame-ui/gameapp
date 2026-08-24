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
Schema::create('aviator_rounds', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('round_no')->unique();
    $table->string('status', 20)->default('betting')->index();

    $table->decimal('crash_multiplier', 10, 2);
    $table->unsignedInteger('betting_duration_ms')->default(8000);
    $table->unsignedInteger('flight_duration_ms');
    $table->unsignedInteger('result_duration_ms')->default(3000);
    $table->decimal('slow_factor', 5, 2)->default(1.45);

    $table->dateTime('betting_starts_at')->index();
    $table->dateTime('flying_starts_at')->index();
    $table->dateTime('crashed_at')->index();
    $table->dateTime('finished_at')->index();
    $table->dateTime('settled_at')->nullable()->index();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_rounds');
    }
};
