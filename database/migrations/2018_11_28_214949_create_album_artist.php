<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlbumArtist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('album_artists', function (Blueprint $table) {
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
        Schema::table('album_artists', function (Blueprint $table) {
            $table->dropForeign('album_artists_album_artist_id_foreign');
        });
        Schema::drop('artists');
    }
}
