<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AccessListSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(PreferencesSeeder::class);
        $this->call(PreferenceHasRolesSeeder::class);
    }
}
