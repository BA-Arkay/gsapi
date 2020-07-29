<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocatedItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('located_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('item')->comment = 'item_identifier';
            $table->string('location')->comment = 'box_identifier';
            $table->float('weight')->default(0)->comment = 'item weight';
            $table->tinyInteger('is_received')->default(0);
            $table->dateTime('received_at')->nullable();
            $table->string('received_by')->nullable();
            $table->tinyInteger('is_boxed')->default(0);
            $table->dateTime('boxed_at')->nullable();
            $table->string('boxed_by')->nullable();
            $table->tinyInteger('is_moved')->default(0);
            $table->string('moved_from')->nullable();
            $table->dateTime('moved_at')->nullable();
            $table->string('moved_by')->nullable();
            $table->tinyInteger('is_delivered')->default(0);
            $table->dateTime('delivered_at')->nullable();
            $table->string('delivered_by')->nullable();
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
        Schema::dropIfExists('located_items');
    }
}
