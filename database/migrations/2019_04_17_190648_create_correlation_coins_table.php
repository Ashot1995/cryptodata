<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorrelationCoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correlation_coins', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cryptocurrency_id');
            $table->string('symbol');
            $table->timestamps();

            $table->foreign('cryptocurrency_id')->references('cryptocurrency_id')->on('cryptocurrencies')
                    ->onUpdate('cascade')->onDelete('cascade');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('correlation_coins');
    }
}
