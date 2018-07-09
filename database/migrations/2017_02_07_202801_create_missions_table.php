<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 100)->nullable();
            $table->string('display_name', 100)->nullable();
            $table->string('terrain', 50)->nullable();
            $table->string('slug', 100)->nullable();
            $table->string('author', 100)->nullable();
            $table->float('day_time');
            $table->tinyInteger('hidden')->default(0);
            $table->string('addon_version', 10);
            $table->timestamp('last_event_time');
            $table->smallInteger('last_mission_time')->nullable();
            $table->string('file_name', 200)->nullable();

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
        Schema::dropIfExists('missions');
    }
}
