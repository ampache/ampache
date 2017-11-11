<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Faker\Provider\DateTime;
use Illuminate\Support\Facades\Hash;
use App\Models\BaseModel;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();
        $date = $this->getRandomTimestamps();
        DB::table('users')->insert([
                'fullname' => 'Ernie D',
                'username' => 'user0',
                'email' => 'wagnered@comcast.net',
                'password' => Hash::make('excel1223'),
                'access' => 100,
                 BaseModel::CREATED_AT => $date['updated_at'] ,
                 BaseModel::UPDATED_AT => $date['updated_at']
            ]);

        for ($i = 1; $i < 5; ++$i) {
            $date = $this->getRandomTimestamps();
            DB::table('users')->insert([
                'fullname' => 'Nom' . sprintf("%'.02d", $i),
                'username' => 'user' . sprintf("%'.02d", $i),
                'email' => 'email' . sprintf("%'.02d", $i) . '@dummy.com',
                'password' => Hash::make('password' . $i),
                'access' => ($i == 0) ? 100 : 25,
                 BaseModel::CREATED_AT => $date['created_at'] ,
                 BaseModel::UPDATED_AT => $date['updated_at']
                ]);
        }
    }

    private function getRandomTimestamps()
    {
        $date               = array();
        $faker              = Faker::create();
        $date['created_at'] = DateTime::dateTime();
        do {
            $date['updated_at'] = DateTime::dateTime();
        } while ($date['updated_at'] <= $date['created_at']);

        return $date;
    }
}
