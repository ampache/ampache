<?php

use Illuminate\Database\Seeder;

class ImageTableSeeder extends Seeder {

    public function run()
    {
        DB::table('images')->delete();
        
        
    }
}
