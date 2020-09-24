<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cryptocurrency_id')->unsigned();
            $table->string('symbol');
            $table->decimal('price', 32, 16);
            $table->decimal('volume_24h', 32,  16);
            $table->decimal('percent_change_7d', 32, 16);
            $table->decimal('market_cap', 32, 16);
            $table->string('last_updated'); //dateTimeTz
            $table->timestamps();
        });

        Schema::table('quotes', function($table) {
            $table->foreign('cryptocurrency_id')->references('cryptocurrency_id')->on('cryptocurrencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quotes');
    }
}
