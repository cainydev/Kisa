<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->json('activities')->nullable()->change();
            $table->json('product_categories')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('activities')->nullable()->change();
            $table->string('product_categories')->nullable()->change();
        });
    }
};
