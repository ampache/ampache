<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFollowersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_followers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')
                  ->unsigned();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->integer('follow_user_id')
                  ->unsigned();
            $table->foreign('follow_user_id')
                  ->references('id')
                  ->on('users')
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
        Schema::table('user_followers', function(Blueprint $table) {
            $table->dropForeign('user_followers_user_id_foreign');
            $table->dropForeign('user_followers_follow_user_id_foreign');
        });
        Schema::drop('user_followers');
    }
}
