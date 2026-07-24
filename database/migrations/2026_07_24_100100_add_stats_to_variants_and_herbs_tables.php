<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->json('stats')->nullable();
        });

        Schema::table('herbs', function (Blueprint $table) {
            $table->json('stats')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropColumn('stats');
        });

        Schema::table('herbs', function (Blueprint $table) {
            $table->dropColumn('stats');
        });
    }
};
