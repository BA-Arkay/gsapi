<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchWeightToBookedLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_locations', function (Blueprint $table) {
            //
            $table->float('batch_weight')->after('box_barcode')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booked_locations', function (Blueprint $table) {
            //
            $table->dropColumn('batch_weight');
        });
    }
}
