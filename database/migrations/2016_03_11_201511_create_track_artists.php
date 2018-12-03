<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTrackArtists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('track_artists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('prefix', 32)
                  ->nullable();
            $table->string('mbid', 1369)
                  ->nullable();
            $table->text('summary')
                  ->nullable();
            $table->string('place_formed')
                  ->nullable();
            $table->integer('year_formed')
                  ->nullable();
            $table->integer('album_id');
            $table->timestamps();
            $table->engine = 'InnoDB';
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
        Schema::drop('artists');
    }
}
