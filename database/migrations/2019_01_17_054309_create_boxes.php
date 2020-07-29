<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('boxes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('barcode');
            $table->integer('store_id');
            $table->integer('rack_id');
            $table->unsignedInteger('shelf_id');
            $table->string('title');
            $table->string('identifier');
            $table->float('capacity');
            $table->float('occupied')->default(0)->comment('Capacity summery');
            $table->float('booked_quantity')->default(0)->comment('Booked Quantity');
            $table->float('actual_free_space')->default(0)->comment('(capacity - occupied)');
            $table->float('bookable_free_space')->default(0)->comment('(capacity - booked_quantity)');
            $table->integer('number_of_items')->default(0)->comment('(record for number of items)');
            $table->tinyInteger('is_active')->default(0)->comment('flag');
            $table->timestamps();
            $table->foreign('shelf_id')->references('id')->on('shelves')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('boxes');
    }
}
