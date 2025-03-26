<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('billbee_id');
            $table->string('order_number')->nullable();
            $table->dateTime('date');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('platform')->nullable();
            $table->float('total');
            $table->string('currency');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
