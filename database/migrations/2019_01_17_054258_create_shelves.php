<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShelves extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shelves', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('store_id');
            $table->unsignedInteger('rack_id');
            $table->string('title');
            $table->string('identifier')->nullable();
            $table->tinyInteger('is_active')->default(0)->comment('flag');
            $table->timestamps();
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shelves');
    }
}
