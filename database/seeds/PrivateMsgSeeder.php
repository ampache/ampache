<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Faker\Provider\DateTime;

class PrivateMsgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::table('private_msgs')->delete();

        for ($i = 1; $i < 10; ++$i) {
            $date = $this->getRandomTimestamps(rand());
            DB::table('private_msgs')->insert([
                'subject' => 'Subject' . $i,
                'message' => 'Message' . $i,
                'is_read' => false,
                'from_user_id' => $i,
                'to_user_id' => 1,
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
