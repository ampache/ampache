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
            $table->string('name', 128)->nullable()->default(null);
            $table->string('value', 255)->nullable()->default(null);
            $table->string('description', 255)->nullable()->default(null);
            $table->unsignedInteger('level')->default(100);
            $table->string('type', 128)->nullable()->default(null);
            $table->string('category', 128)->nullable()->default(null);
            $table->string('subcategory', 128)->nullable()->default(null);
            $table->index('category');
            $table->index('name');
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
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropIndex(['name']); // Drops index 'geo_state_index'
            $table->dropIndex(['category']); // Drops index 'geo_state_index'
        });
        Schema::drop('preferences');
    }
}

// ENGINE=MyISAM AUTO_INCREMENT=146 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
