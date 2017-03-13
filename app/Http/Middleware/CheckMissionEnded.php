<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\EventController;

class CheckMissionEnded
{
    /**
     * Check a mission has ended otherwise don't return data
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (EventController::missionFinished($request->id))
            return $next($request);
        else
            return response()->json(['error' => 'Mission not finished'], 403);
    }
}
