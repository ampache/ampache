<?php

use Illuminate\Database\Seeder;

class ShareTableSeeder extends Seeder {

    public function run()
    {
        DB::table('shares')->delete();
        
        
    }
}
