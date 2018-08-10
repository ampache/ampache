<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 128)->index()
                  ->nullable();
            $table->string('value', 255)
                  ->nullable();
            $table->string('description', 255)
                  ->nullable();
            $table->integer('level', 255)
                  ->nullable();
            $table->string('type', 128)
                  ->nullable();
            $table->string('category', 128)->index()
                  ->nullable();
            $table->string('subcategory', 128)
                  ->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('preferences');
    }
}
