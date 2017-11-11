<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShoutTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('shouts')->delete();
    }
}
