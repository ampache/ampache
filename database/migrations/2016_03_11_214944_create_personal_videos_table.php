<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_videos', function (Blueprint $table) {
            $table->integer('id')
                  ->unsigned();
            $table->foreign('id')
                  ->references('id')
                  ->on('videos')
                  ->onDelete('cascade');
            $table->primary('id');
            $table->string('location')
                  ->nullable();
            $table->string('summary')
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_videos', function(Blueprint $table) {
            $table->dropForeign('personal_videos_id_foreign');
        });
        Schema::drop('personal_videos');
    }
}
