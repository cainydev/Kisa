<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The core tables were created with foreignIdFor(), which only adds the
 * integer column, not a database-level foreign key. This adds the missing
 * constraints so orphaned bags/positions/variants can no longer exist.
 *
 * Existing orphans (rows pointing at a parent that no longer exists) are
 * removed first, mirroring the cleanup the ingredients FK migration had to do.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->deleteOrphans('bags', 'herb_id', 'herbs');
        $this->deleteOrphans('bottle_positions', 'bottle_id', 'bottles');
        $this->deleteOrphans('bottle_positions', 'variant_id', 'variants');
        $this->deleteOrphans('bottles', 'user_id', 'users');
        $this->deleteOrphans('herb_product', 'herb_id', 'herbs');
        $this->deleteOrphans('herb_product', 'product_id', 'products');
        $this->deleteOrphans('variants', 'product_id', 'products');
        $this->deleteOrphans('order_positions', 'order_id', 'orders');
        $this->nullifyOrphans('bags', 'delivery_id', 'deliveries');
        $this->nullifyOrphans('order_positions', 'variant_id', 'variants');

        Schema::table('bags', function (Blueprint $table) {
            $table->foreign('herb_id')->references('id')->on('herbs')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('delivery_id')->references('id')->on('deliveries')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->foreign('bottle_id')->references('id')->on('bottles')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('variant_id')->references('id')->on('variants')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('bottles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('herb_product', function (Blueprint $table) {
            $table->foreign('herb_id')->references('id')->on('herbs')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('product_id')->references('id')->on('products')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('order_positions', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('variant_id')->references('id')->on('variants')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('bags', function (Blueprint $table) {
            $table->dropForeign(['herb_id']);
            $table->dropForeign(['delivery_id']);
        });

        Schema::table('bottle_positions', function (Blueprint $table) {
            $table->dropForeign(['bottle_id']);
            $table->dropForeign(['variant_id']);
        });

        Schema::table('bottles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('herb_product', function (Blueprint $table) {
            $table->dropForeign(['herb_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_positions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['variant_id']);
        });
    }

    /**
     * Delete rows whose non-nullable foreign key points at a missing parent.
     */
    private function deleteOrphans(string $table, string $column, string $parent): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, fn ($query) => $query->select('id')->from($parent))
            ->delete();
    }

    /**
     * Null out a nullable foreign key that points at a missing parent.
     */
    private function nullifyOrphans(string $table, string $column, string $parent): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, fn ($query) => $query->select('id')->from($parent))
            ->update([$column => null]);
    }
};
