<?php

use Illuminate\Database\Seeder;

class PrivateMsgTableSeeder extends Seeder {

    public function run()
    {
        DB::table('private_msgs')->delete();
        
        
    }
}
