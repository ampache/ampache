<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaylistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('libitem_type', 32);
            $table->integer('libitem_id')
                  ->unsigned();
            $table->integer('track')
                  ->unsigned();
            $table->integer('playlist_id')->unsigned();
            $table->foreign('playlist_id')
                  ->references('id')
                  ->on('playlists')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlist_items', function(Blueprint $table) {
            $table->dropForeign('playlist_items_playlist_id_foreign');
        });
        Schema::drop('playlist_items');
    }
}
