<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->string('charge')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->dropColumn('charge');
        });
    }
};
