<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Faker\Provider\DateTime;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();
           $date = $this->getRandomTimestamps();
           DB::table('users')->insert([
                'id' => 0,
                'fullname' => 'Ernie D',
                'username' => 'admin',
                'email' => 'wagnered@comcast.net',
                'password' => Hash::make('excel1223'),
                'access' => 100,
                'created_at' => $date['created_at'] ,
                'updated_at' => $date['updated_at']
            ]);

        for ($i = 1; $i < 10; ++$i) {
            $date = $this->getRandomTimestamps();
            DB::table('users')->insert([
                'fullname' => 'Nom' . $i,
                'username' => 'user' . $i,
                'email' => 'email' . $i . '@dummy.com',
                'password' => Hash::make('password' . $i),
                'access' => ($i == 0) ? 100 : 25,
                'created_at' => $date['created_at'] ,
                'updated_at' => $date['updated_at']
            ]);
        }
    }

    private function getRandomTimestamps()
    {
        $date = array();
        $faker = Faker::create();
       $date['created_at'] = DateTime::unixTime();
       do {
           $date['updated_at'] = DateTime::unixTime();
       }
       while ($date['updated_at'] <= $date['created_at'] );
           
        return $date;
    }

}