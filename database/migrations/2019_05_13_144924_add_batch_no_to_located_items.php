<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchNoToLocatedItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('located_items', function (Blueprint $table) {
            //
            $table->string('batch_no')->after('weight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('located_items', function (Blueprint $table) {
            //
            $table->dropColumn('batch_no');
        });
    }
}
