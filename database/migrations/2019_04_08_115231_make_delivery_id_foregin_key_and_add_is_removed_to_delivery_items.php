<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeDeliveryIdForeginKeyAndAddIsRemovedToDeliveryItems extends Migration
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
//            $table->tinyInteger('is_removed')->after('quantity')->default(0);
//            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
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
//            $table->dropColumn('is_removed');
        });
    }
}
