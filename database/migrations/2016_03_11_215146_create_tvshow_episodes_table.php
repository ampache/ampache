<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTvshowEpisodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tvshow_episodes', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('videos')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->string('original_name', 80)
                  ->nullable();
            $table->string('summary')
                  ->nullable();
            $table->integer('episode_number');
            $table->integer('season_id')
                  ->unsigned();
            $table->foreign('season_id')
                  ->references('id')
                  ->on('tvshow_seasons')
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
        Schema::table('tvshow_episodes', function(Blueprint $table) {
            $table->dropForeign('tvshow_episodes_id_foreign');
            $table->dropForeign('tvshow_episodes_season_id_foreign');
        });
        Schema::drop('tvshow_episodes');
    }
}
