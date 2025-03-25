<?php

use App\Models\Bottle;
use App\Models\Variant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bottle_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bottle::class);
            $table->foreignIdFor(Variant::class);
            $table->integer('count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bottle_positions');
    }
};
