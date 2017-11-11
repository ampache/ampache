<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LiveStreamTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('live_streams')->delete();
    }
}
