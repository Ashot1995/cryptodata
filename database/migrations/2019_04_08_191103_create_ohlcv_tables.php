<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOhlcvTables extends Migration
{
    const EXCHANGE_NAMES = ['CMC'];
    const TIME_FRAMES = ['30d', '1w', '1d', '12h', '4h', '1h', '30m', '15m', '5m', '1m'];
    const DEFAULT_TABLE_PREFIX = 'ohlcv_';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = $this->getTableNames();
        foreach ($tableNames as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('base_id');
                $table->integer('quote_id');
                $table->decimal('open', 32, 16)->nullable();
                $table->decimal('high', 32, 16)->nullable();
                $table->decimal('low', 32, 16)->nullable();
                $table->decimal('close', 32, 16)->nullable();
                $table->decimal('volume', 32, 16)->nullable();
                $table->decimal('market_cap', 32, 16)->nullable();
                $table->dateTime('timestamp')->nullable();
                $table->string('time_open')->nullable();
                $table->string('time_close')->nullable();
                $table->timestamps();


            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = $this->getTableNames();
        foreach ($tableNames as $table) {
            Schema::dropIfExists($table);
        }
    }

    protected function getTableNames(): array
    {
        $tableNames = [];
        foreach (self::EXCHANGE_NAMES as $name) {
            $timeFrames = ($name === 'CMC') ? array_slice(self::TIME_FRAMES, 0, 6) : self::TIME_FRAMES;
            $name = str_replace(' ', '_', strtolower($name));
            foreach ($timeFrames as $timeFrame) {
                $tableNames[] = self::DEFAULT_TABLE_PREFIX . $name . '_' . $timeFrame;
            }
        }
        return $tableNames;
    }
}
