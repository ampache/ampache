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
            $table->smallInteger('level', 3)->index()
                  ->default('5');
            $table->string('type', 64)
                  ->nullable();
            $table->integer('user');
            $table->boolean('enabled')->index()
                  ->default('1');
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
        Schema::dropIfExists('_a_c_l');
    }
}
