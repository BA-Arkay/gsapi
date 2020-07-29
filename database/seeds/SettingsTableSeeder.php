<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'key' => 'unit',
                'value' => 'kg'
            ],
            [
                'key' => 'barcode',
                'value' => 'title'
            ],
            [
                'key' => 'api-item-weight',
                'value' => 'http://lumen-store.test/testItem/'
            ],
            [
                'key' => 'item-age-unit',
                'value' => 6
            ],
            [
                'key' => 'item-age',
                'value' => 10
            ],


        ];
        DB::table('settings')->insert($data);
    }
}
