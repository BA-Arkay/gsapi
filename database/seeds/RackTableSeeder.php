<?php

use Illuminate\Database\Seeder;

class RackTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('racks')->insert([
            'store_id' => '1',
            'title' => 'Default Rack',
            'identifier' => 'ds-dr'
        ]);
    }
}
