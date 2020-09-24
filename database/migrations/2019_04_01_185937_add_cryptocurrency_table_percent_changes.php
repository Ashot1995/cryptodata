<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCryptocurrencyTablePercentChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->float('percent_changes_weekly', 32, 16)->nullable()->after('circulating_supply');
            $table->float('percent_changes_monthly', 32, 16)->nullable()->after('percent_changes_weekly');
            $table->float('percent_changes_three_month', 32, 16)->nullable()->after('percent_changes_monthly');
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

