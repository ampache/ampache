<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSharesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->increments('id');
            $table->string('libitem_type', 32);
            $table->integer('libitem_id')
                  ->unsigned();
            $table->boolean('allow_stream')->default(false);
            $table->boolean('allow_download')->default(false);
            $table->mediumInteger('expire_days');
            $table->mediumInteger('max_counter');
            $table->mediumInteger('counter');
            $table->string('secret', 20);
            $table->string('public_url');
            $table->string('description');
            $table->timestamp('lastvisit_date');
            $table->timestamps();
            $table->integer('user_id')
                  ->unsigned();
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
        Schema::table('shares', function(Blueprint $table) {
            $table->dropForeign('shares_user_id_foreign');
        });
        Schema::drop('shares');
    }
}
