<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('insta_target');
            $table->enum('type', ['follow', 'like', 'comment']);
            $table->string('post_url', 255)->nullable();
            $table->unsignedInteger('points');
            $table->boolean('active')->default(true); 
            $table->timestamps();
          
            $table->foreign('insta_target')->references('id')->on('user_insta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_requests');
    }
}
