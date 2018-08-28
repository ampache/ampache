<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('preference_id');
            $table->integer('role_id');
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
        $table->dropForeign('fk_preference_permissions_1');
        $table->dropForeign('fk_preference_roles_1');
        Schema::dropIfExists('permission_roles');
    }
}
