<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGlobalMetricsHistoricalRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_metrics_historical_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('time_start')->nullable(); //dateTimeTz
            $table->string('time_end')->nullable(); //dateTimeTz
            $table->integer('count')->nullable();
            $table->string('interval', 10)->nullable();
            $table->string('convert', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('global_metrics_historical_requests');
    }
}
