<?php

use Illuminate\Database\Seeder;

class ShelvesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('shelves')->insert([
            'store_id' => '1',
            'rack_id' => '1',
            'title' => 'Default Shelf',
            'identifier' => 'ds-dr-ds'
        ]);
    }
}
