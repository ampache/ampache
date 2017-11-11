<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClipTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('clips')->delete();
    }
}
