<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BroadcastTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('broadcasts')->delete();
    }
}
