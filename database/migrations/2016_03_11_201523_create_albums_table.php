<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateAlbumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('prefix', 32)
                  ->nullable();
            $table->string('mbid', 36)
                  ->nullable();
            $table->integer('year');
            $table->smallInteger('disk');
            $table->string('mbid_group', 36)
                  ->nullable();
            $table->string('release_type', 32)
                  ->nullable();
            $table->timestamps();
            $table->unsignedInteger('album_artist_id')
                  ->nullable();
            $table->foreign('album_artist_id')
                  ->references('id')
                  ->on('artists')
                  ->onDelete('cascade');
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
        Schema::table('albums', function (Blueprint $table) {
            $table->dropForeign('albums_album_artist_id_foreign');
        });
        Schema::drop('albums');
    }
}
