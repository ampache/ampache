<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserActivityTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('user_activities')->delete();
    }
}
