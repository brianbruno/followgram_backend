<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordResetsNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('password_resets');
      
        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 150)->index();
            $table->string('token', 255);
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
        Schema::dropIfExists('password_resets');
      
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email', 150)->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
}
