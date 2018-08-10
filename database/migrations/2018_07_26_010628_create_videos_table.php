<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->increments('id');
            $table->string('file', 4096)
            ->nullable()->default('NULL');
            $table->unsignedInteger('catalog');
            $table->string('title')
                  ->nullable()->default('NULL');
            $table->string('video_codec')
                  ->nullable()->default('NULL');
            $table->string('audio_codec')
                  ->nullable()->default('NULL');
            $table->mediumInteger('resolution_x')
                  ->unsigned();
            $table->mediumInteger('resolution_y')
                  ->unsigned();
            $table->timestamp('time');
            $table->bigInteger('size', 20)
                  ->unsigned();
            $table->string('mime')
                  ->default('NULL');
            $table->tinyInteger('enabled', 1)
                  ->default('1');
            $table->tinyInteger('played', 1)
                  ->unsigned()->default('0');
            $table->timestamp('release_date')
                  ->default('NULL');
            $table->mediumInteger('channels', 9)
                  ->default('NULL');
            $table->mediumInteger('bitrate', 8)
                  ->default('NULL');
            $table->mediumInteger('video_bitrate', 8)
                  ->default('NULL');
            $table->mediumInteger('display_x', 8)
                  ->default('NULL');
            $table->mediumInteger('display_y', 8)
                  ->default('NULL');
            $table->float('frame_rate')
                  ->default('NULL');
            $table->enum('mode', ['abr', 'vbr', 'cbr'])
                  ->devault('NULL');
            $table->timestamps();
            $table->index(['update_time', 'addition_time', 'file', 'enabled', 'title']);
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
        //$table->dropIndex(['update_time', 'addition_time', 'file', 'enabled', 'title']);
        Schema::dropIfExists('videos');
    }
}
