<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWantedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wanteds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('artist_mbid', 1369)
                  ->nullable();
            $table->string('mbid', 36)
                  ->nullable();
            $table->string('name')
                  ->nullable();
            $table->mediumInteger('year')
                  ->nullable();
            $table->boolean('accepted')
                  ->default(false);
            $table->timestamps();
            $table->integer('user_id')
                  ->unsigned();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->integer('artist_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('artist_id')
                  ->references('id')
                  ->on('artists')
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
        Schema::table('wanteds', function(Blueprint $table) {
            $table->dropForeign('wanteds_user_id_foreign');
            $table->dropForeign('wanteds_artist_id_foreign');
        });
        Schema::drop('wanteds');
    }
}
