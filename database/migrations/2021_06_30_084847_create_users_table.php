<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->bigInteger('id');
            $table->primary('id');
            $table->string('username',100);
            $table->unique('username');
            $table->string('email',100);
            $table->unique('email');
            $table->string('password',200);
            $table->string('name',100);
            $table->tinyInteger('gender')->nullable();
            $table->string('nohp',15)->nullable();
            $table->string('address',500)->nullable();
            $table->tinyInteger('experience')->nullable();
            $table->string('photo',200)->nullable();
            $table->uuid('token')->nullable();
            $table->enum('role',['admin','author','reader']);
            $table->tinyInteger('active')->default('0');
            $table->uuid('uuid');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();
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
