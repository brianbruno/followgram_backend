<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFollowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_follow', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('insta_target');
            $table->unsignedBigInteger('insta_following');
            $table->unsignedInteger('points');
            $table->boolean('confirmed')->default(false); 
            $table->boolean('canceled')->default(false); 
            $table->timestamps();
          
            $table->foreign('insta_target')->references('id')->on('user_insta');
            $table->foreign('insta_following')->references('id')->on('user_insta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_follow');
    }
}
