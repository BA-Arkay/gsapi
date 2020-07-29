<?php

use Illuminate\Database\Seeder;

class StoreTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('stores')->insert([
            'title' => 'Default Store',
            'location' => 'Ground Floor'
        ]);
    }
}
