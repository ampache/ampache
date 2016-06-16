<?php

use Illuminate\Database\Seeder;

class BookmarkTableSeeder extends Seeder {

    public function run()
    {
        DB::table('bookmarks')->delete();
        
        
    }
}
