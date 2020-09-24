<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCryptocurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
           
            $table->dropColumn('max_price');
            $table->dropColumn('atn_percent');
            $table->timestamp('ath_date')->nullable();
            $table->float('ath', 32, 16)->nullable();
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
            $table->float('atn_percent', 32, 16)->nullable();
            $table->float('max_price', 32, 16)->nullable();

            $table->dropColumn('ath_date');
            $table->dropColumn('ath');
        });
    }
}
