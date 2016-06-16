<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTvshowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tvshows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')
                  ->nullable();
            $table->string('prefix', 32)
                  ->nullable();
            $table->text('summary')
                  ->nullable();
            $table->integer('year')
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
        Schema::drop('tvshows');
    }
}
