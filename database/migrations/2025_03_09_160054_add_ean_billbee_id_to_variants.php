<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->string('billbee_id')->nullable();
            $table->string('ean')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->removeColumn('billbee_id');
            $table->removeColumn('ean');
        });
    }
};
