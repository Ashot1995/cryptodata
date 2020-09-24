<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHistoricalDataCryptocurrencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->decimal('circulating_supply', 36, 16)->nullable()->change();
            $table->string('date_added')->nullable()->change(); //dateTimeTz
            $table->string('last_updated')->nullable()->change(); //dateTimeTz
            $table->integer('num_market_pairs')->nullable()->change();
            $table->integer('cmc_rank')->nullable()->change();
            $table->string('currency_type')->nullable();
            $table->decimal('total_supply', 32, 16)->nullable()->change();
            $table->integer('is_active')->default(1);
            $table->dateTimeTz('first_historical_data')->nullable();
            $table->dateTimeTz('last_historical_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
          //
        });
    }
}
