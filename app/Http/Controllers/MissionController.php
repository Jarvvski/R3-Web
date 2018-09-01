<?php

namespace App\Http\Controllers;

use App\Mission;
use App\InfantryPosition;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Setting;

class MissionController extends Controller
{
    private $selectPlayerCount = "
        COUNT(distinct infantry.player_id) as player_count,
        GROUP_CONCAT(IF(infantry.player_id='', NULL, infantry.player_id) SEPARATOR ',') as raw_player_list";

    public function __construct()
    {
        Carbon::setLocale(config('app.locale'));
        $this->currentTime = Carbon::now(config('app.timezone'));

        $this->secondsMissionEndBlock = 20;
    }
    /**
     * @SWG\Get(
     *     tags={"Missions"},
     *     path="/missions",
     *     summary="Finds all visible missions",
     *     description="Returns all missions that aren't hidden",
     *     @SWG\Response(
     *         response=200,
     *         description="A list of all visible missions"
     *     )
     * )
     */
    public function fetchAllVisible()
    {

        $missions = DB::table('missions')
                    ->select(
                        'missions.*',
                        DB::raw($this->selectPlayerCount)
                    )
                    ->leftJoin('infantry', 'infantry.mission', '=', 'missions.id')
                    ->where('missions.hidden', 0)
                    ->where('infantry.player_id', '<>', '')
                    ->groupBy('missions.id')
                    ->orderBy('created_at', 'desc')
                    ->get();

        foreach($missions as $index => $mission) {

            // Generate extra data for consumption
            $mission = $this->setHumanTimes($mission);

            $mission->length_in_minutes = round($mission->last_mission_time / 60);

            $mission->in_progress_block = ($mission->seconds_since_last_event < $this->secondsMissionEndBlock) ? true : false;

            // Generate and save a slug if required
            $mission->slug = $this->generateSlug($mission);

            unset($mission->raw_player_list);

            $isTooShort = $mission->length_in_minutes < (int) Setting::get('minutesMinimumMission', 2);
            $notEnoughPlayers = $mission->player_count < (int) Setting::get('minimumMissionPlayers', 2);

            // If the mission has finished, did it run for long enough and have enough players not to be auto hidden?
            if(!$mission->in_progress_block && $isTooShort && $notEnoughPlayers) {
                $this->hide($mission->id);
                unset($missions[$index]);
            }
        }

        return $missions;
    }

    public function hide($missionId = false)
    {
        if($missionId)
            return DB::table('missions')
                ->where('id', $missionId)
                ->update(['hidden' => 1]);
    }

    private function generateSlug($mission = null)
    {

        if(!$mission || $mission->slug)
            return $mission->slug;

        $missionName = ($mission->display_name != "") ? $mission->display_name : $mission->name;

        $slug = str_slug($missionName);

        Mission::whereId($mission->id)->update(['slug' => $slug]);

        return $slug;
    }

    /**
     * @SWG\Get(
     *     tags={"Missions"},
     *     path="/missions/all",
     *     summary="Finds all missions hidden or otherwise",
     *     description="Returns all missions, needs web token admin auth",
     *     @SWG\Response(
     *         response=200,
     *         description="A list of all missions"
     *     )
     * )
     */
    public function fetchAll()
    {
        return Mission::orderBy('id', 'desc')->get();
    }

    private function setHumanTimes($mission)
    {
        $lastEventTime = Carbon::parse($mission->last_event_time, 'UTC');
        $lastEventTime->setTimezone(config('app.timezone'));

        $missionStart = Carbon::parse($mission->created_at, 'UTC');
        $missionStart->setTimezone(config('app.timezone'));

        $mission->minutes_since_last_event = $lastEventTime->diffInMinutes($this->currentTime);
        $mission->seconds_since_last_event = $lastEventTime->diffInSeconds($this->currentTime);
        $mission->length_human = humanTimeDifference($lastEventTime, $missionStart);
        $mission->played_human = humanEventOccuredFromNow($missionStart);

        return $mission;
    }

    /**
     * @SWG\Get(
     *     tags={"Missions"},
     *     path="/missions/{missionId}",
     *     summary="Find mission by Id",
     *     description="Find mission by Id that isn't hidden",
     *     @SWG\Parameter(
     *         description="Id of mission to return",
     *         in="path",
     *         name="missionId",
     *         required=true,
     *         default=1,
     *         type="integer",
     *         format="int64"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="A single mission"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Mission not found"
     *     )
     * )
     */
    public function fetchOne($id)
    {
        $mission = DB::table('missions')
                    ->select(
                        'missions.*',
                        DB::raw($this->selectPlayerCount)
                    )
                    ->leftJoin('infantry', 'infantry.mission', '=', 'missions.id')
                    ->where('missions.hidden', 0)
                    ->where('missions.id', $id)
                    ->groupBy('missions.id')
                    ->first();

        if($mission) {
            $mission = $this->setHumanTimes($mission);
            return response()->json($mission);
        } else {
            return response()->json(['error' => 'Not Found'], 404);
        }
    }

    /**
     * @SWG\Get(
     *     tags={"Missions"},
     *     path="/missions/delete/{missionId}",
     *     summary="Delete mission by Id",
     *     description="Delete mission by Id",
     *     @SWG\Parameter(
     *         description="Id of mission to delete",
     *         in="path",
     *         name="missionId",
     *         required=true,
     *         type="integer",
     *         format="int64"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="empty response indicating success"
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="No User or user is not admin"
     *     )
     * )
     */
    public function delete(Request $request, $id)
    {
        if(!$request->user()->admin) {
            return response()->json(['error' => 'Not Allowed'], 403);
        }
        $mission = DB::table('missions')
                    ->delete($id);

        if($mission) {
            return response()->json();
        } else {
            return response()->json(['error' => 'Not Found'], 404);
        }
    }

    public static function missionFinished($missionId)
    {
        $getLastMissionEvent = DB::table('missions')
                    ->select('last_event_time')
                    ->where('id', $missionId)
                    ->first();

        Carbon::setLocale(config('app.locale'));
        $currentTime = Carbon::now(config('app.timezone'));

        $lastEventTime = Carbon::parse($getLastMissionEvent->last_event_time, 'UTC');
        $lastEventTime->setTimezone(config('app.timezone'));

        $secondsSinceLastEvent = $lastEventTime->diffInSeconds($currentTime);

        return ($secondsSinceLastEvent < 20) ? false : true;
    }
}
