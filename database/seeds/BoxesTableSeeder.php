<?php

use Illuminate\Database\Seeder;

class BoxesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('boxes')->insert([
            'barcode' => 'default-barcode',
            'store_id' => '1',
            'rack_id' => '1',
            'shelf_id' => '1',
            'title' => 'Default Box',
            'identifier' => 'ds-dr-ds-db',
            'capacity' => '-1',
        ]);
    }
}
