<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTnIndexesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tn_indexes', function (Blueprint $table) {
            $table->increments('id');
            $table->double('tn200')->nullable();
            $table->double('tn100')->nullable();
            $table->double('tn50')->nullable();
            $table->double('tn10')->nullable();
            $table->dateTime('timestamp')->nullable();
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
        Schema::dropIfExists('tn_indexes');
    }
}
