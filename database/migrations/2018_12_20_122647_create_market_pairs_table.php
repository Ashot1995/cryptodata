<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketPairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_pairs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('string1_id')->nullable()->unsigned();
            $table->integer('string2_id')->nullable()->unsigned();
            $table->timestamps();
        });

        Schema::table('market_pairs', function($table) {
            $table->foreign('string1_id')->references('cryptocurrency_id')->on('cryptocurrencies');
            $table->foreign('string2_id')->references('cryptocurrency_id')->on('cryptocurrencies');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_pairs');
    }
}
