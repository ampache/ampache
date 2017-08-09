<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TmpPlaylistDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmp_playlist_data', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tmp_playlist');
            $table->string('object_type', 32)->nullable();
            $table->integer('object_id');
            $table->integer('track')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tmp_playlist_data');
    }
}
