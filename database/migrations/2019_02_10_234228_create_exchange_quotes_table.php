<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangeQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('exchange_id')->unsigned();
            $table->string('symbol');
            $table->float('volume_24h', 32,  16)->nullable();
            $table->float('volume_24h_adjusted', 32,  16)->nullable();
            $table->float('volume_7d', 32,  16)->nullable();
            $table->float('volume_30d', 32,  16)->nullable();
            $table->float('percent_change_volume_24h', 32,  16)->nullable();
            $table->float('percent_change_volume_7d', 32,  16)->nullable();
            $table->float('percent_change_volume_30d', 32,  16)->nullable();
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
        Schema::dropIfExists('exchange_quotes');
    }
}
