<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ReadsCSVData;

use App\Models\{Product, Herb, Supplier};

class HerbPercentageSeeder extends Seeder
{

    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $galke = Supplier::firstWhere('shortname', 'Galke');
        $medisoil = Supplier::firstWhere('shortname', 'Medi-Soil');

        // Adding all the Herbs and setting the percentages
        $herbs = self::getCSV('tabelle.CSV', ';');
        foreach ($herbs as $herb) {

            $supplier = $galke;
            $name = $herb['Name'];

            if (str($name)->contains('Bergtee')) {
                $name = "Griechischer Bergtee";
                $supplier = $medisoil;
            } else {
                $name = explode(' ', trim($herb["Name"]))[0];
            }

            $newHerb = Herb::create([
                'name' => $name,
                'fullname' => $herb["Name"],
                'supplier_id' => $supplier->id
            ]);

            array_pop($herb);
            array_shift($herb);

            foreach ($herb as $mischung => $percent) {
                $product = Product::where('name', 'like', '%' . explode(' ', trim($mischung))[0] . '%')->first();
                $percentage = floatval($percent);

                if ($product == null)
                    $this->command->info("Failed, please look at: " . $mischung);

                if ($percentage > 0) {
                    DB::table('herb_product')->insert([
                        'herb_id' => $newHerb->id,
                        'product_id' => $product->id,
                        'percentage' => $percentage
                    ]);
                }
            }
        }
    }
}
