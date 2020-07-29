<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOvBatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ov_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('batch_no')->union();
            $table->integer('buyer_id')->default(0);
            $table->string('buyer_name')->nullable();
            $table->string('order_no')->nullable();
            $table->string('style_no')->nullable();
            $table->tinyInteger('color_id')->default(0);
            $table->string('color')->nullable();
            $table->string('color_type')->nullable();
            $table->string('size')->nullable();
            $table->string('fabric_type')->nullable();
            $table->string('dia')->nullable();
            $table->string('gauge')->nullable();
            $table->string('gsm')->nullable();
            $table->text('yarn_info')->nullable();
            $table->float('batch_weight')->default(0);
            $table->integer('num_items')->default(0);
            $table->string('location')->nullable();
            $table->integer('item_produced')->default(0);
            $table->float('qty_produced')->default(0);
            $table->integer('item_stored')->default(0);
            $table->float('qty_stored')->default(0);
            $table->integer('item_delivered')->default(0);
            $table->float('qty_delivered')->default(0);
            $table->integer('current_stock_items')->default(0);
            $table->tinyInteger('is_delivered')->default(0);
            $table->timestamp('production_start_at')->nullable();
            $table->timestamp('stock_start_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('delivery_ref')->nullable();
            $table->timestamp('last_sync_at')->nullable();
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
        Schema::dropIfExists('ov_batches');
    }
}
