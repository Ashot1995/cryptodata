<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOhlcvCmcTableName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('ohlcv_cmc_1h', 'ohlcv_cmc_1h_old');
        Schema::rename('ohlcv_cmc_4h', 'ohlcv_cmc_4h_old');
        Schema::rename('ohlcv_cmc_1w', 'ohlcv_cmc_1w_old');
        Schema::rename('ohlcv_cmc_1d', 'ohlcv_cmc_1d_old');
        Schema::rename('ohlcv_cmc_12h', 'ohlcv_cmc_12h_old');
        Schema::rename('ohlcv_cmc_30d', 'ohlcv_cmc_30d_old');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
