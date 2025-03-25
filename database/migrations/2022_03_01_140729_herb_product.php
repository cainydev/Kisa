<?php

use App\Models\Herb;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('herb_product', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Herb::class);
            $table->foreignIdFor(Product::class);
            $table->decimal('percentage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herb_product');
    }
};
