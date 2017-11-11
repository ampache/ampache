<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WantedTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('wanteds')->delete();
    }
}
