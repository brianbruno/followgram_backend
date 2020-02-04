<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPrivateToUserInstaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_insta', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('biography');
            $table->boolean('is_verified')->default(false)->after('is_private');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_insta', function (Blueprint $table) {
            $table->dropColumn(['is_private', 'is_verified']);
        });
    }
}
