<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLiveStreamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')
                  ->nullable();
            $table->string('site_url')
                  ->nullable();
            $table->string('url', 4096);
            $table->string('codec', 32)
                  ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('live_streams');
    }
}
