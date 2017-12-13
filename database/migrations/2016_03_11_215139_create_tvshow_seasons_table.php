<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTvshowSeasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tvshow_seasons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('season_number');
            $table->integer('tvshow_id')
                  ->unsigned();
            $table->foreign('tvshow_id')
                  ->references('id')
                  ->on('tvshows')
                  ->onDelete('cascade');
            $table->timestamps();
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
        Schema::table('tvshow_seasons', function (Blueprint $table) {
            $table->dropForeign('tvshow_seasons_tvshow_id_foreign');
        });
        Schema::drop('tvshow_seasons');
    }
}
