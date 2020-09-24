<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAlphaBetaToCoefficientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coefficients', function (Blueprint $table) {
            $table->float('alpha', 32, 16)->nullable()->after('sharpe');
            $table->float('beta', 32, 16)->nullable()->after('alpha');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coefficients', function (Blueprint $table) {
            //
        });
    }
}
