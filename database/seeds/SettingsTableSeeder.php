<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'key' => 'unitName',
            'value' => 'Set this in the DB'
        ]);

        DB::table('settings')->insert([
            'key' => 'minutesMinimumMission',
            'value' => 5
        ]);

        DB::table('settings')->insert([
            'key' => 'minimumMissionPlayers',
            'value' => 1
        ]);

        DB::table('settings')->insert([
            'key' => 'minimumPlaybackSpeed',
            'value' => 1
        ]);

        DB::table('settings')->insert([
            'key' => 'maximumPlaybackSpeed',
            'value' => 31
        ]);
    }
}
