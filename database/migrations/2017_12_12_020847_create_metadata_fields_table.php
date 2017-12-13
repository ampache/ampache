<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetadataFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metadata_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->tinyInteger('public');
            $table->text('data');
            $table->string('type', 50)->nullable();
            $table->engine = 'MYISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->primary('id');
            $table->unique(`name`);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        schema::drop('metadata_fields');
    }
}
