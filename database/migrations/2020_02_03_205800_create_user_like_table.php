<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLikeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_like', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('insta_target');
            $table->unsignedBigInteger('insta_liking');
            $table->unsignedInteger('points');
            $table->enum('status', ['pending', 'confirmed', 'canceled'])->default('pending');	
            $table->timestamps();
          
            $table->foreign('request_id')->references('id')->on('user_requests');
            $table->foreign('insta_target')->references('id')->on('user_insta');
            $table->foreign('insta_liking')->references('id')->on('user_insta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_like');
    }
}
