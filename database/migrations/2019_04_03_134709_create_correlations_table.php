<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorrelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correlations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_id');
            $table->unsignedInteger('quote_id');
            $table->decimal('correlation', 32, 16)->nullable();
            $table->string('interval');
            $table->dateTime('timestamp')->nullable();
            $table->timestamps();
            

            
            $table->foreign('base_id')->references('cryptocurrency_id')->on('cryptocurrencies');
            $table->foreign('quote_id')->references('cryptocurrency_id')->on('cryptocurrencies');



            
        });
       

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('correlations');
    }
}
