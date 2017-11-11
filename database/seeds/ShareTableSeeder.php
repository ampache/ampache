<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShareTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('shares')->delete();
    }
}
