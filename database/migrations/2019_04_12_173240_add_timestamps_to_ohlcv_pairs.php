<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimestampsToOhlcvPairs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ohlcv_pairs', function (Blueprint $table) {
            $table->timestamp('30d_first_date')->nullable();
            $table->timestamp('1w_first_date')->nullable();
            $table->timestamp('1d_first_date')->nullable();
            $table->timestamp('12h_first_date')->nullable();
            $table->timestamp('4h_first_date')->nullable();
            $table->timestamp('1h_first_date')->nullable();
            $table->timestamp('30m_first_date')->nullable();
            $table->timestamp('15m_first_date')->nullable();
            $table->timestamp('5m_first_date')->nullable();
            $table->timestamp('1m_first_date')->nullable();
            $table->timestamp('30d_last_date')->nullable();
            $table->timestamp('1w_last_date')->nullable();
            $table->timestamp('1d_last_date')->nullable();
            $table->timestamp('12h_last_date')->nullable();
            $table->timestamp('4h_last_date')->nullable();
            $table->timestamp('1h_last_date')->nullable();
            $table->timestamp('30m_last_date')->nullable();
            $table->timestamp('15m_last_date')->nullable();
            $table->timestamp('5m_last_date')->nullable();
            $table->timestamp('1m_last_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ohlcv_pairs', function (Blueprint $table) {
            $table->dropColumn('30d_first_date')->nullable();
            $table->dropColumn('1w_first_date')->nullable();
            $table->dropColumn('1d_first_date')->nullable();
            $table->dropColumn('12h_first_date')->nullable();
            $table->dropColumn('4h_first_date')->nullable();
            $table->dropColumn('1h_first_date')->nullable();
            $table->dropColumn('30m_first_date')->nullable();
            $table->dropColumn('15m_first_date')->nullable();
            $table->dropColumn('5m_first_date')->nullable();
            $table->dropColumn('1m_first_date')->nullable();
            $table->dropColumn('30d_last_date')->nullable();
            $table->dropColumn('1w_last_date')->nullable();
            $table->dropColumn('1d_last_date')->nullable();
            $table->dropColumn('12h_last_date')->nullable();
            $table->dropColumn('4h_last_date')->nullable();
            $table->dropColumn('1h_last_date')->nullable();
            $table->dropColumn('30m_last_date')->nullable();
            $table->dropColumn('15m_last_date')->nullable();
            $table->dropColumn('5m_last_date')->nullable();
            $table->dropColumn('1m_last_date')->nullable();
        });
    }
}
