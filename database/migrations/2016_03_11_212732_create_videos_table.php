<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('medias')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->string('title')
                  ->nullable();
            $table->string('video_codec')
                  ->nullable();
            $table->string('audio_codec')
                  ->nullable();
            $table->mediumInteger('resolution_x');
            $table->mediumInteger('resolution_y');
            $table->timestamp('release_date')
                  ->nullable();
            $table->mediumInteger('video_bitrate')
                  ->nullable();
            $table->mediumInteger('display_x')
                  ->nullable();
            $table->mediumInteger('display_y')
                  ->nullable();
            $table->mediumInteger('frame_rate')
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
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign('videos_id_foreign');
        });
        Schema::drop('videos');
    }
}
