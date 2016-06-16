<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrivateMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('private_msgs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subject')
                  ->nullable();
            $table->text('message')
                  ->nullable();
            $table->boolean('is_read')
                  ->default(false);
            $table->timestamps();
            $table->integer('from_user_id')->unsigned();
            $table->foreign('from_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->integer('to_user_id')->unsigned();
            $table->foreign('to_user_id')
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
        Schema::table('private_msgs', function(Blueprint $table) {
            $table->dropForeign('private_msgs_from_user_id_foreign');
            $table->dropForeign('private_msgs_to_user_id_foreign');
        });
        Schema::drop('private_msgs');
    }
}
