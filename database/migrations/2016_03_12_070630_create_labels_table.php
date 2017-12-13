<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 80)
                  ->nullable();
            $table->string('category', 40)
                  ->nullable();
            $table->text('summary')
                  ->nullable();
            $table->string('address')
                  ->nullable();
            $table->string('email', 128)
                  ->nullable();
            $table->string('website')
                  ->nullable();
            $table->timestamps();
            $table->integer('user_id')
                  ->unsigned()
                  ->nullable();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
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
        Schema::table('labels', function (Blueprint $table) {
            $table->dropForeign('labels_user_id_foreign');
        });
        Schema::drop('labels');
    }
}
