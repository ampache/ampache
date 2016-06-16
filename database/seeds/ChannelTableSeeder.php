<?php

use Illuminate\Database\Seeder;

class ChannelTableSeeder extends Seeder {

    public function run()
    {
        DB::table('channels')->delete();
        
        
    }
}
