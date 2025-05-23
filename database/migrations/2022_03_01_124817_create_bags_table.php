<?php

use App\Models\Delivery;
use App\Models\Herb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bags', function (Blueprint $table) {
            $table->id();
            $table->string('charge');
            $table->boolean('bio')->default(true);
            $table->integer('size');
            $table->string('specification');
            $table->integer('trashed')->default(0);
            $table->foreignIdFor(Herb::class);
            $table->foreignIdFor(Delivery::class)->nullable();
            $table->date('bestbefore');
            $table->date('steamed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bags');
    }
};
