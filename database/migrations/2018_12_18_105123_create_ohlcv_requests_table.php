<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOhlcvRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ohlcv_requests', function (Blueprint $table) {
            $table->increments('request_id');
            $table->integer('id')->nullable();
            $table->string('symbol');
            $table->string('time_start'); //dateTimeTz
            $table->string('time_end'); //dateTimeTz
            $table->integer('count');
            $table->string('interval', 10);
            $table->string('time_period', 10);
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
        Schema::dropIfExists('ohlcv_requests');
    }
}
