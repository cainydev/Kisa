<?php

use App\Models\Bag;
use App\Models\BottlePosition;
use App\Models\Herb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BottlePosition::class);
            $table->foreignIdFor(Herb::class);
            $table->foreignIdFor(Bag::class);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
