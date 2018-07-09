<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDefaultSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')->insert(
            array(
                'key' => 'minimumPlaybackSpeed',
                'value' => 5
            )

        );
        DB::table('settings')->insert(
            array(
                'key' => 'maximumPlaybackSpeed',
                'value' => 30
            )

        );
        DB::table('settings')->insert(
            array(
                'key' => 'unitName',
                'value' => 'SET IN DB SETTINGS'
            )

        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
}
