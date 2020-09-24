<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalMetricsHistoricalQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_metrics_historical_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('timestamp')->nullable();
            $table->float('btc_dominance', 32, 16)->nullable();
            $table->string('convert');
            $table->float('total_market_cap', 32, 16)->nullable();
            $table->float('total_volume_24h', 32, 16)->nullable();
            $table->dateTime('timestamp_quote')->nullable();
            $table->string('interval', 10)->nullable();
            $table->integer('count')->nullable();
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
        Schema::dropIfExists('global_metrics_historical_quotes');
    }
}
