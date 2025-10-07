<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable integer 'position' column to the tasks table.
     * Default 0 keeps backward compatibility.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'position')) {
                $table->integer('position')->default(0)->after('product_type')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'position')) {
                $table->dropColumn('position');
            }
        });
    }
}