<?php

use Illuminate\Database\Seeder;

class FavoriteTableSeeder extends Seeder {

    public function run()
    {
        DB::table('favorites')->delete();
        
        
    }
}
