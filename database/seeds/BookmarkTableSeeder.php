<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookmarkTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('bookmarks')->delete();
    }
}
