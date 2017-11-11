<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalVideoTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('personal_videos')->delete();
    }
}
