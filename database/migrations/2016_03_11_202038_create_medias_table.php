<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('file', 4096);
            $table->bigInteger('size');
            $table->integer('time');
            $table->boolean('played');
            $table->boolean('enabled')->default(true);
            $table->string('mime')
                  ->nullable();
            $table->mediumInteger('bitrate')
                  ->nullable();
            $table->enum('mode', array('abr', 'vbr', 'cbr'))
                  ->nullable();
            $table->mediumInteger('channels')
                  ->nullable();
            $table->timestamps();
            $table->integer('catalog_id')
                  ->unsigned();
            $table->foreign('catalog_id')
                  ->references('id')
                  ->on('catalogs')
                  ->onDelete('cascade');
            $table->integer('license_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('license_id')
                  ->references('id')
                  ->on('licenses')
                  ->onDelete('set null');
            $table->integer('user_upload_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('user_upload_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medias', function(Blueprint $table) {
            $table->dropForeign('medias_catalog_id_foreign');
            $table->dropForeign('medias_license_id_foreign');
            $table->dropForeign('medias_user_upload_id_foreign');
        });
        Schema::drop('medias');
    }
}
