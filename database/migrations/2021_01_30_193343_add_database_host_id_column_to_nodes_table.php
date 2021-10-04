<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatabaseHostIdColumnToNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('database_host_id')->nullable()->unsigned()->after('location_id');
            $table->index('database_host_id')->nullable();
            $table->foreign('database_host_id')->references('id')->on('database_hosts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign(['database_host_id']);
            $table->dropColumn('database_host_id');
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            $table->integer('node_id')->nullable()->unsigned()->after('max_databases');
            $table->index('node_id')->nullable();
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
