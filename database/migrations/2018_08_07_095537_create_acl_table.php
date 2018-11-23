<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateACLTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_list', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)
                  ->nullable();
            $table->ipAddress('start')->index();
            $table->ipAddress('end')->index();
            $table->smallInteger('level')
                  ->default('25');
            $table->string('type', 64)
                  ->nullable();
            $table->integer('user');
            $table->boolean('enabled')->index()
                  ->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_list');
    }
}
