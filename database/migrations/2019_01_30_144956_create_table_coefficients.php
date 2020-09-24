<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCoefficients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coefficients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cryptocurrency_id')->unsigned();
            $table->string('convert');
            $table->float('volatility', 32, 16)->nullable();
            $table->float('sharpe', 32, 16)->nullable();
            $table->string('interval');
            $table->dateTime('c_date');
            $table->timestamps();
        });

        Schema::table('coefficients', function($table) {
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
        Schema::dropIfExists('coefficients');
    }
}
