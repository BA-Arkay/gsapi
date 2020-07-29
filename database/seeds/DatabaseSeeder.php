<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            StoreTableSeeder::class,
            RackTableSeeder::class,
            ShelvesTableSeeder::class,
			BoxesTableSeeder::class,
            SettingsTableSeeder::class,
        ]);
    }
}
