<?php

use App\Models\Order;
use App\Models\Variant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Order::class);
            $table->foreignIdFor(Variant::class)->nullable();

            $table->string('billbee_id');

            $table->integer('quantity');
            $table->float('price');
            $table->float('tax_percent');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_positions');
    }
};
