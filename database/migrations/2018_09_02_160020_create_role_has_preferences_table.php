<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleHasPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_has_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('preference_id');
            $table->unsignedInteger('role_id');
            $table->foreign('preference_id')
            ->references('id')->on('preferences');
            $table->foreign('role_id')
            ->references('id')->on('roles');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_has_preferences', function (Blueprint $table) {
            $table->dropForeign('preference_id');
            $table->dropForeign('role_id');
        });
        Schema::dropIfExists('role_has_preferences');
    }
}
