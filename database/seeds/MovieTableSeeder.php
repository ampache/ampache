<?php

use Illuminate\Database\Seeder;

class MovieTableSeeder extends Seeder {

    public function run()
    {
        DB::table('movies')->delete();
        
        
    }
}
