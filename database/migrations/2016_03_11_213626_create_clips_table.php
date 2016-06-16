<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clips', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('videos')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->integer('artist_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('artist_id')
                  ->references('id')
                  ->on('artists')
                  ->onDelete('set null');
            $table->integer('song_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('song_id')
                  ->references('id')
                  ->on('songs')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clips', function(Blueprint $table) {
            $table->dropForeign('clips_id_foreign');
            $table->dropForeign('clips_artist_id_foreign');
            $table->dropForeign('clips_song_id_foreign');
        });
        Schema::drop('clips');
    }
}
