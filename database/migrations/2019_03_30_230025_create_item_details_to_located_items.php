<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemDetailsToLocatedItems extends Migration
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
            $table->text('item_detail')->nullable()->after('weight');
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
            $table->dropColumn('item_detail');
        });
    }
}
