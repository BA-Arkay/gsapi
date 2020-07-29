<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->string('title');
            $table->string('identifier')->nullable();
            $table->tinyInteger('is_active')->default(0)->comment('flag');
            $table->timestamps();
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('racks');
    }
}
