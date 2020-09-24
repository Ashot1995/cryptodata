<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTnIndexCoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tn_index_coins', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cryptocurrency_id');
            $table->string('index_name');
            $table->boolean('default')->default(false);
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
        Schema::dropIfExists('tn_index_coins');
    }
}
