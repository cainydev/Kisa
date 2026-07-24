<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeze the grams drawn from a bag onto each ingredient. Existing rows
     * are backfilled with the current recipe formula (what readers computed
     * on the fly until now). Rows whose product recipe no longer contains
     * the herb backfill to 0, matching their previous dropped contribution.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->after('bag_id');
        });

        DB::statement(<<<'SQL'
            UPDATE ingredients i
            JOIN bottle_positions bp ON i.bottle_position_id = bp.id
            JOIN variants v ON bp.variant_id = v.id
            LEFT JOIN herb_product hp ON hp.product_id = v.product_id AND hp.herb_id = i.herb_id
            SET i.amount = ROUND(COALESCE(v.size * bp.count * hp.percentage / 100, 0), 2)
        SQL);

        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
