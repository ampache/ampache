<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->binary('image')
                  ->nullable();
            $table->string('size', 64)
                  ->nullable();
            $table->mediumInteger('width')
                  ->default(0);
            $table->mediumInteger('height')
                  ->default(0);
            $table->string('image_type', 32);
            $table->integer('image_id')
                  ->unsigned();
            $table->string('mime', 64)
                  ->nullable();
            $table->string('kind', 32)
                  ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('images');
    }
}
