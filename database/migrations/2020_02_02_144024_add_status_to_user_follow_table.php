<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToUserFollowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_follow', function (Blueprint $table) {
            $table->dropColumn(['pending', 'confirmed', 'canceled']);
          
            $table->enum('status', ['pending', 'confirmed', 'canceled'])->default('pending')->after('points');	
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_follow', function (Blueprint $table) {
            $table->boolean('pending')->default(false)->after('points'); 
            $table->boolean('confirmed')->default(false)->after('pending'); 
            $table->boolean('canceled')->default(false)->after('confirmed'); 
          
            $table->dropColumn('status');
        });
    }
}
