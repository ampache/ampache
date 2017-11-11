<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrivateMsgTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('private_msgs')->delete();
    }
}
