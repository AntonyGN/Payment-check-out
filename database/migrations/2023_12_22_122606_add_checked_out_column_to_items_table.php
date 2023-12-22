<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckedOutColumnToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('items', function (Blueprint $table) {
        $table->boolean('checked_out')->default(false)->after('status');
        
    });
}

    public function down()
{
    Schema::table('items', function (Blueprint $table) {
        $table->dropColumn('checked_out');
        
    });

    }
}
