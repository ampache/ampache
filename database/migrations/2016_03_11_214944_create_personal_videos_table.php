<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('personal_videos', function (Blueprint $table) {
            $table->dropForeign('personal_videos_id_foreign');
        });
        Schema::drop('personal_videos');
    }
}
