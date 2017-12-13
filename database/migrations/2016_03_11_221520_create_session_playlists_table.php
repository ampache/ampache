<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateSessionPlaylistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_playlists', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('playlists')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->string('session')
                  ->nullable();
            $table->string('type', 32)
                  ->nullable();
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
        Schema::table('session_playlists', function (Blueprint $table) {
            $table->dropForeign('session_playlists_id_foreign');
        });
        Schema::drop('session_playlists');
    }
}
