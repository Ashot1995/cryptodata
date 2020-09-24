<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalMetricQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_metrics_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('global_metric_id');
            $table->string('symbol');
            $table->float('total_market_cap', 32, 16)->nullable();
            $table->float('total_volume_24h', 32, 16)->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->timestamps();

            $table->foreign('global_metric_id', 'gmq_gmi_foreign')
                ->references('id')->on('global_metrics')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('global_metrics', function (Blueprint $table) {
            $table->dropForeign('gmq_gmi_foreign');
        });

        Schema::dropIfExists('global_metrics_quotes');
    }
}
