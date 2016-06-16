<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLabelMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('label_maps', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('label_id')
                  ->unsigned();
            $table->foreign('label_id')
                  ->references('id')
                  ->on('labels')
                  ->onDelete('cascade');
            $table->integer('artist_id')
                  ->unsigned();
            $table->foreign('artist_id')
                  ->references('id')
                  ->on('artists')
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
        Schema::table('label_maps', function(Blueprint $table) {
            $table->dropForeign('label_maps_label_id_foreign');
            $table->dropForeign('label_maps_artist_id_foreign');
        });
        Schema::drop('label_maps');
    }
}
