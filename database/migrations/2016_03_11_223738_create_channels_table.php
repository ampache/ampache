<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)
                  ->nullable();
            $table->string('description')
                  ->nullable();
            $table->string('url')
                  ->nullable();
            $table->string('interface', 64)
                  ->nullable();
            $table->integer('port');
            $table->boolean('fixed_endpoint')->default(false);
            $table->boolean('is_private')->default(false);
            $table->boolean('random')->default(false);
            $table->boolean('loop')->default(false);
            $table->string('admin_password', 20)
                  ->nullable();
            $table->timestamp('start_date');
            $table->integer('max_listeners')
                  ->default(0);
            $table->integer('peak_listeners')
                  ->default(0);
            $table->integer('listeners')
                  ->default(0);
            $table->integer('connections')
                  ->default(0);
            $table->string('stream_type', 8)
                  ->nullable();
            $table->integer('bitrate')
                  ->default(0);
            $table->integer('pid')
                  ->default(0);
            $table->string('libitem_type', 32);
            $table->integer('libitem_id')
                  ->unsigned();
            $table->mediumInteger('rating');
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
        Schema::drop('channels');
    }
}
