<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shouts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('libitem_type', 32);
            $table->integer('libitem_id')
                  ->unsigned();
            $table->text('text');
            $table->boolean('sticky')
                  ->default(false);
            $table->string('data')
                  ->nullable();
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
        Schema::table('shouts', function(Blueprint $table) {
            $table->dropForeign('shouts_user_id_foreign');
        });
        Schema::drop('shouts');
    }
}
