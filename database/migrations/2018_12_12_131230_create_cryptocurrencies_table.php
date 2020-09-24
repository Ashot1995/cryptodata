<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCryptocurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->increments('cryptocurrency_id');
            $table->integer('id');
            $table->string('name');
            $table->string('symbol');
            $table->string('slug');
            $table->decimal('circulating_supply', 36, 16);
            $table->decimal('max_supply', 36, 16)->nullable();
            $table->string('date_added'); //dateTimeTz
            $table->string('last_updated'); //dateTimeTz
            $table->integer('num_market_pairs');
            $table->integer('platform_id')->nullable()->unsigned();
            $table->integer('cmc_rank');

            $table->timestamps();
        });

        Schema::table('cryptocurrencies', function($table) {
            $table->foreign('platform_id')->references('platform_id')->on('platforms')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cryptocurrencies');
    }
}
