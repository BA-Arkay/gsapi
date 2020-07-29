<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchNumberOfItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('boxes', function (Blueprint $table) {
            //
            $table->integer('batch_rolls')->default(0)->after('batch_barcode')->comment('total rolls of batch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropColumn('batch_rolls');
        });
    }
}
