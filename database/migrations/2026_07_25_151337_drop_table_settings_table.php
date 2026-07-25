<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the table_settings table. It backed a pre-Filament table renderer
     * that no longer exists; nothing in the application reads it.
     */
    public function up(): void
    {
        Schema::dropIfExists('table_settings');
    }

    public function down(): void
    {
        Schema::create('table_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tablename');
            $table->string('alias');
            $table->json('options');
            $table->timestamps();
        });
    }
};
