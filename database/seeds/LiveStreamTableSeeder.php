<?php

use Illuminate\Database\Seeder;

class LiveStreamTableSeeder extends Seeder {

    public function run()
    {
        DB::table('live_streams')->delete();
        
        
    }
}
