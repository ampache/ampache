<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Faker\Provider\DateTime;
use App\Models\BaseModel;

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

        for ($i = 1; $i < 30; ++$i) {
            $date = $this->getRandomTimestamps(rand());
            DB::table('private_msgs')->insert([
                'subject' => 'Subject' . sprintf("%'.02d", $i),
                'message' => 'Message' . sprintf("%'.02d", $i),
                'is_read' => false,
                'from_user_id' => $i + 1,
                'to_user_id' => 1,
                 BaseModel::CREATED_AT => $date['created_at'] ,
                 BaseModel::UPDATED_AT => $date['updated_at']
                ]);
        }
        
        for ($i = 1; $i < 9; ++$i) {
            $date = $this->getRandomTimestamps(rand());
            DB::table('private_msgs')->insert([
                'subject' => 'Subject' . sprintf("%'.02d", $i),
                'message' => 'Message' . sprintf("%'.02d", $i),
                'is_read' => false,
                'from_user_id' => 1,
                'to_user_id' => $i + 1,
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
