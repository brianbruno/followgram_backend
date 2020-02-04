<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountdetailsToUserInstaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_insta', function (Blueprint $table) {
            $table->text('profile_pic_url')->nullable(true)->after('username');
            $table->string('external_url', 255)->nullable(true)->after('profile_pic_url');
            $table->string('full_name', 255)->nullable(true)->after('external_url');
            $table->longText('biography')->nullable(true)->after('full_name');
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
            $table->dropColumn(['profile_pic_url', 'external_url', 'full_name', 'biography']);
        });
    }
}
