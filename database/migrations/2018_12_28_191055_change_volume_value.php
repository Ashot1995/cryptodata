<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeVolumeValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ohlcv_quotes', function (Blueprint $table) {
            $table->decimal('open', 32, 16)->nullable()->change();
            $table->decimal('high', 32, 16)->nullable()->change();
            $table->decimal('low', 32, 16)->nullable()->change();
            $table->decimal('close', 32, 16)->nullable()->change();
            $table->decimal('volume', 32, 16)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ohlcv_quotes', function (Blueprint $table) {
            //
        });
    }
}
