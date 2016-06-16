<?php

use Illuminate\Database\Seeder;

class LicenseTableSeeder extends Seeder {

    public function run()
    {
        DB::table('licenses')->delete();
    }
}
