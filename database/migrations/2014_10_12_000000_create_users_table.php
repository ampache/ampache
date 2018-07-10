<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username');
            $table->string('full_name', 225)
                  ->nullable();
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->string('subsonic_password', 25)
                  ->nullable();
            $table->tinyInteger('access')
                  ->default(25);
            $table->string('website', 225)
                  ->nullable();
            $table->string('apikey')
                  ->nullable();
            $table->boolean('disabled')
                  ->default(false);
            $table->boolean('verified')
                  ->default(false);     
            $table->string('validation')
                  ->nullable();
            $table->timestamp('last_seen');
            $table->boolean('name_public')
                  ->default(false);
            $table->string('street')
                  ->nullable();
            $table->string('country')
                  ->nullable();
            $table->string('city')
                  ->nullable();
            $table->string('state')
                  ->nullable();
            $table->string('zip')
                  ->nullable();
            $table->string('settings', 4096)
                  ->nullable();
            $table->binary('avatar')
                  ->nullable();
            $table->rememberToken();
            $table->string('email_token')
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
        Schema::dropIfExists('users');
    }
}
