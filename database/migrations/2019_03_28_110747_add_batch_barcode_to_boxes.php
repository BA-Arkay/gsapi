<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchBarcodeToBoxes extends Migration
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
            $table->string('batch_barcode')->after('bookable_free_space')->nullable();
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
            //
            $table->dropColumn('bookable_free_space');
        });
    }
}
