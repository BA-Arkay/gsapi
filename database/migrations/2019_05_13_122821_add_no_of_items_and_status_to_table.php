<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNoOfItemsAndStatusToTable extends Migration
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
            $table->integer('number_of_items')->nullable();
            $table->string('status', 40)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookedLocation', function (Blueprint $table) {
            $table->dropColumn('number_of_items');
            $table->dropColumn('status');
        });
    }
}
