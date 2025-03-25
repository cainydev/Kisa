<?php

use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('herbs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Unbenannt');
            $table->string('fullname')->default('Unbenanntes Kraut');
            $table->foreignIdFor(Supplier::class);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herbs');
    }
};
