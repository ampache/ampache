<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjectCount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('object_count', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('object_type', ['album', 'artist' . 'song', 'playlist', 'genre', 'catalog',
            'live_stream', 'video', 'podcast_episode'])->nullable();
            $table->unsignedInteger('object_id');
            $table->unsignedInteger('date')->default(0);
            $table->integer('user');
            $table->string('agent', 254)->nullable();
            $table->decimal('geo_latitude', 10, 6)->nullable();
            $table->decimal('geo_longitude', 10, 6)->nullable();
            $table->string('geo_name', 254)->nullable();
            $table->string('count_type', 16)->nullable();
            $table->engine = 'MYISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('object_count');
    }
}
