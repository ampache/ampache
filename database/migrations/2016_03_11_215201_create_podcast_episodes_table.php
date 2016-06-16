<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePodcastEpisodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('podcast_episodes', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('videos')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->string('title')
                  ->nullable();
            $table->string('guid')
                  ->nullable();
            $table->string('state', 32)
                  ->nullable();
            $table->string('source', 4096)
                  ->nullable();
            $table->string('website')
                  ->nullable();
            $table->string('description', 4096)
                  ->nullable();
            $table->string('author', 64)
                  ->nullable();
            $table->string('category', 64)
                  ->nullable();
            $table->timestamp('pubdate');
            $table->integer('podcast_id')
                  ->unsigned();
            $table->foreign('podcast_id')
                  ->references('id')
                  ->on('podcasts')
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
        Schema::table('podcast_episodes', function(Blueprint $table) {
            $table->dropForeign('podcast_episodes_id_foreign');
            $table->dropForeign('podcast_episodes_podcast_id_foreign');
        });
        Schema::drop('podcast_episodes');
    }
}
