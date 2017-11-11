<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('catalog_type', 32);
            $table->timestamp('last_update');
            $table->timestamp('last_clean');
            $table->timestamp('last_add');
            $table->boolean('enabled')->default(true);
            $table->string('rename_pattern');
            $table->string('sort_pattern');
            $table->string('gather_types');
            $table->boolean('public')->default(true);
            $table->integer('owner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('catalogs');
    }
}
