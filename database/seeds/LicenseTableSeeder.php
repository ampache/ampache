<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LicenseTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('licenses')->delete();
    }
}
