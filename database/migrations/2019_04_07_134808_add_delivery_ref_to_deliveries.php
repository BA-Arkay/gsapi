<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryRefToDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivered_items', function (Blueprint $table) {
            //
            $table->string('delivery_reference')->nullable()->after('delivery_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivered_items', function (Blueprint $table) {
            //
            $table->dropColumn('delivery_reference');
        });
    }
}
