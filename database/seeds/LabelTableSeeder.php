<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('labels')->delete();
    }
}
