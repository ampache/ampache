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
            $table->timestamp('last_update')->useCurrent();
            $table->timestamp('last_clean')->useCurrent();
            $table->timestamp('last_add')->useCurrent();
            $table->boolean('enabled')->default(true);
            $table->string('rename_pattern');
            $table->string('sort_pattern');
            $table->string('gather_types');
            $table->boolean('public')->default(true);
            $table->integer('owner');
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
        Schema::dropIfExists('catalogs');
    }
}
