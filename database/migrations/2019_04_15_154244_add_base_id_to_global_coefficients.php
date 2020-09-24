<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBaseIdToGlobalCoefficients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_coefficients', function (Blueprint $table) {
            $table->unsignedInteger('base_id')->nullable()->after('id');
            $table->unsignedInteger('quote_id')->nullable()->after('base_id');

            $table->foreign('base_id')->references('cryptocurrency_id')->on('cryptocurrencies')
                    ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('quote_id')->references('cryptocurrency_id')->on('cryptocurrencies')
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
        Schema::table('global_coefficients', function (Blueprint $table) {
            $table->dropColumn('base_id');
            $table->dropColumn('quote_id');
        });
    }
}
