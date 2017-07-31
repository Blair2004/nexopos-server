<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->increments('id');
            $table->string( 'host' );
            $table->string( 'ip' );
            $table->string( 'app_name' );
            $table->string( 'app_version' );
            $table->string( 'app_code' )->nullable();
            $table->string( 'google_code' )->nullable();
            $table->string( 'google_access_token' )->nullable();
            $table->string( 'google_refresh_token' )->nullable();
            $table->datetime( 'google_token_expire' )->nullable();
            $table->string( 'google_token_type' )->nullable();
            $table->string( 'envato_licence' )->nullable();
            $table->string( 'gcp_proxy' )->nullable();
            $table->string( 'request_uri' );
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
        Schema::dropIfExists('apis');
    }
}
