<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBroadcastsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('libitem_type', 32);
            $table->integer('libitem_id')
                  ->unsigned();
            $table->string('name', 64)
                  ->nullable();
            $table->string('description')
                  ->nullable();
            $table->string('key', 32)
                  ->nullable();
            $table->boolean('is_private')
                  ->default(false);
            $table->boolean('started')
                  ->default(false);
            $table->integer('listeners')
                  ->default(0);
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')
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
        Schema::table('broadcasts', function(Blueprint $table) {
            $table->dropForeign('broadcasts_user_id_foreign');
        });
        Schema::drop('broadcasts');
    }
}
