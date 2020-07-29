<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveredItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivered_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('delivery_id')->comment('Delivery ID');
            $table->string('item_identifier');
            $table->string('quantity');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivered_items');
    }
}
