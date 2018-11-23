<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            $table->mediumText('file', 512)
            ->nullable();
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
            $table->bigInteger('size')
                  ->unsigned();
            $table->string('mime')
                  ->nullable()->default('NULL');
            $table->tinyInteger('enabled')
                  ->unsigned()->default('1');
            $table->tinyInteger('played')
                  ->unsigned()->default('0');
            $table->timestamp('release_date')
                  ->nullable();
            $table->mediumInteger('channels')
                  ->nullable();
            $table->mediumInteger('bitrate')
                  ->nullable();
            $table->mediumInteger('video_bitrate')
                  ->nullable();
            $table->mediumInteger('display_x')
                  ->nullable();
            $table->mediumInteger('display_y')
                  ->nullable();
            $table->float('frame_rate')
                  ->nullable();
            $table->enum('mode', ['abr', 'vbr', 'cbr'])
                  ->default('cbr');
            $table->timestamps();
            $table->engine = 'MYISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
        DB::raw('CREATE INDEX part_of_file ON video (file(100))');
        DB::raw('CREATE INDEX part_of_title ON video (title(100))');
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
