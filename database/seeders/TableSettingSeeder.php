<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\TableSetting;

class TableSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Bags Table
        TableSetting::create([
            'tablename' => 'bags',
            'alias' => 'Säcke',
            'options' => [
                'edit' => true,
                'delete' => true,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'charge' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => '# %s',
                        'width' => '1fr',
                        'alias' => 'Charge',
                        'description' => 'Nr.'
                    ],
                    'bio' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '0.5fr',
                        'alias' => 'Bio',
                        'description' => ''
                    ],
                    'size' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => '%ug',
                        'width' => '1fr',
                        'alias' => 'Gebinde',
                        'description' => ''
                    ],
                    'current' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => '%ug',
                        'width' => '1fr',
                        'alias' => 'Füllstand',
                        'description' => 'Aktuell'
                    ],
                    'herb_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'herbs',
                            'column' => 'name'
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Inhalt',
                        'description' => 'Kraut'
                    ],
                    'delivery_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'deliveries',
                            'column' => 'id',
                            'nullable' => true
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Lieferung',
                        'description' => 'ID'
                    ],
                    'bestbefore' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Haltbar bis',
                        'description' => 'Mindesthaltbarkeitsdatum'
                    ],
                    'steamed' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Dampfbehandelt',
                        'description' => 'Datum der letzten Dampfbehandlung'
                    ]
                ]
            ]
        ]);
        // Herbs Table
        TableSetting::create([
            'tablename' => 'herbs',
            'alias' => 'Alle Kräuter',
            'options' => [
                'edit' => true,
                'delete' => true,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'name' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Name',
                        'description' => 'als Kurzform'
                    ],
                    'fullname' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Name',
                        'description' => 'komplett'
                    ],
                    'supplier_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'suppliers',
                            'column' => 'shortname'
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '2fr',
                        'alias' => 'Lieferant',
                        'description' => 'Standardlieferant'
                    ],
                ]
            ]
        ]);

        // Products Table
        TableSetting::create([
            'tablename' => 'products',
            'alias' => 'Endprodukte',
            'options' => [
                'edit' => true,
                'delete' => true,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'name' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1.5fr',
                        'alias' => 'Name',
                        'description' => ''
                    ],
                    'ordernumber' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'Shopware ID',
                        'description' => 'Shopware ordernumber'
                    ],
                    'product_type_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'product_types',
                            'column' => 'name'
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '2fr',
                        'alias' => 'Produkttyp',
                        'description' => 'Siehe Produkttypen'
                    ],
                ]
            ]
        ]);

        // Product_types Table
        TableSetting::create([
            'tablename' => 'product_types',
            'alias' => 'Produkttypen',
            'options' => [
                'edit' => true,
                'delete' => false,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'name' => [
                        'primary' => false,
                        'foreign' => false,
                        'format' => false,
                        'width' => '4fr',
                        'alias' => 'Name',
                        'description' => ''
                    ]
                ]
            ]
        ]);

        // Bio-Inspector Table
        TableSetting::create([
            'tablename' => 'bio_inspectors',
            'alias' => 'Bio-Kontrollstellen',
            'options' => [
                'edit' => true,
                'delete' => false,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'company' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Firma',
                        'description' => ''
                    ],
                    'label' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Identifikationsnummer',
                        'description' => 'Eindeutig'
                    ],
                ]
            ]
        ]);

        // Deliveries Table
        TableSetting::create([
            'tablename' => 'deliveries',
            'alias' => 'Lieferungen',
            'options' => [
                'edit' => true,
                'delete' => true,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'delivered_date' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Lieferdatum',
                        'description' => 'Ankunft der Ware'
                    ],
                    'bio_inspection' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => false,
                        'format' => 'Nicht darstellbar',
                        'width' => '2fr',
                        'alias' => 'Eingangskontrolle',
                        'description' => 'bei Bio Lieferungen'
                    ],
                    'supplier_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'suppliers',
                            'column' => 'shortname',
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '2fr',
                        'alias' => 'Lieferant',
                        'description' => 'dieser Lieferung'
                    ],
                ]
            ]
        ]);

        // Suppliers Table
        TableSetting::create([
            'tablename' => 'suppliers',
            'alias' => 'Lieferanten',
            'options' => [
                'edit' => true,
                'delete' => false,
                'columns' => [
                    'id' => [
                        'primary' => true,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '1fr',
                        'alias' => 'ID',
                        'description' => 'Eindeutig'
                    ],
                    'company' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Firma',
                        'description' => 'Firmenname'
                    ],
                    'contact' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Kontaktperson',
                        'description' => ''
                    ],
                    'email' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Email',
                        'description' => ''
                    ],
                    'website' => [
                        'primary' => false,
                        'foreign' => false,
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Webseite',
                        'description' => ''
                    ],
                    'bio_inspector_id' => [
                        'primary' => false,
                        'foreign' => [
                            'table' => 'bio_inspectors',
                            'column' => 'label'
                        ],
                        'withSort' => true,
                        'format' => false,
                        'width' => '3fr',
                        'alias' => 'Kontrollstelle',
                        'description' => 'Bio-Kontrollstelle'
                    ]
                ]
            ]
        ]);
    }
}
