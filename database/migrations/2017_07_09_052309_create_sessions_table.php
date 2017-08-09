<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->text('username')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity');
            $table->timestamp('logon_date');
            $table->text('type', 16)->nullable();
            $table->integer('expiry')->default(0);
            $table->longText('value');
            $table->decimal('geo_latitude', 10, 6)->nullable();
            $table->decimal('geo_longitude', 10, 6)->nullable();
            $table->text('geo_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
