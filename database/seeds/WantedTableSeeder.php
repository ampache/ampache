<?php

use Illuminate\Database\Seeder;

class WantedTableSeeder extends Seeder {

    public function run()
    {
        DB::table('wanteds')->delete();
        
        
    }
}
