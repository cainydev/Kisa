<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Nr. 64 bottlings of 2020-04-19 (position 276) and 2023-05-05
     * (position 915) were bag-linked against Nr. 63's ingredient list —
     * KISA's Nr. 64 recipe was a duplicate of Nr. 63 at the time and was
     * reformulated later. Value their draws at Nr. 63's percentages
     * (1000 g per bottling), confirmed against the paper recipe book.
     *
     * @var array<int, float> herb_id => grams per bottling
     */
    private const array GRAMS_PER_BOTTLING = [
        14 => 83.00,  // Dostkraut 8.3%
        16 => 98.00,  // Erdrauchkraut 9.8%
        18 => 146.00, // Fenchel 14.6%
        31 => 63.00,  // Kamillenblüten 6.3%
        38 => 205.00, // Löwenzahnkraut 20.5%
        50 => 83.00,  // Schachtelhalmkraut 8.3%
        51 => 98.00,  // Schafgarbenkraut 9.8%
        61 => 127.00, // Wacholderbeeren 12.7%
        62 => 97.00,  // Weißdornblätter 9.7%
    ];

    private const array POSITION_IDS = [276, 915];

    public function up(): void
    {
        foreach (self::GRAMS_PER_BOTTLING as $herbId => $grams) {
            DB::table('ingredients')
                ->whereIn('bottle_position_id', self::POSITION_IDS)
                ->where('herb_id', $herbId)
                ->update(['amount' => $grams]);
        }
    }

    public function down(): void
    {
        // The previous amounts were a backfill artifact; not restored.
    }
};
