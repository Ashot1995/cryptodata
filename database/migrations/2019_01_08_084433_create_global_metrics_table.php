<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_metrics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('active_cryptocurrencies')->nullable();
            $table->integer('active_market_pairs')->nullable();
            $table->integer('active_exchanges')->nullable();
            $table->float('eth_dominance', 8, 5)->nullable();
            $table->float('btc_dominance', 8, 5)->nullable();
            $table->dateTime('last_updated')->nullable();
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
        Schema::dropIfExists('global_metrics');
    }
}
