<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchDetailToBookedLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_locations', function (Blueprint $table) {
            $table->text('batch_detail')->nullable()->after('batch_weight');
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
            $table->dropColumn('batch_detail');
        });
    }
}
