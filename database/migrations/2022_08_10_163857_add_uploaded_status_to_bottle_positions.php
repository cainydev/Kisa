<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds a >uploaded< column to the bottle position. Turns true when
 *  the new Stock is updated in Billbee.
 */

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->boolean('uploaded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->dropColumn('uploaded');
        });
    }
};
