<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelMapTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('label_maps')->delete();
    }
}
