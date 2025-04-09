<?php

use App\Models\Ingredient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Clean up broken references
        Ingredient::with(['position', 'herb', 'bag'])->get()
            ->filter(function (Ingredient $ingredient) {
                return $ingredient->position === null
                    || $ingredient->herb === null
                    || $ingredient->bag === null;
            })
            ->each(function (Ingredient $ingredient) {
                $ingredient->delete();
            });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreign('bottle_position_id')
                ->references('id')
                ->on('bottle_positions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('herb_id')
                ->references('id')
                ->on('herbs')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('bag_id')
                ->references('id')
                ->on('bags')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['bottle_position_id']);
            $table->foreign('bottle_position_id')
                ->references('id')
                ->on('bottle_positions')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->dropForeign(['herb_id']);
            $table->foreign('herb_id')
                ->references('id')
                ->on('herbs')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->dropForeign(['bag_id']);
            $table->foreign('bag_id')
                ->references('id')
                ->on('bags')
                ->noActionOnDelete()
                ->noActionOnUpdate();
        });
    }
};
