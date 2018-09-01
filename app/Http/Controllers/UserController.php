<?php

namespace App\Http\Controllers;

use App\Mission;
use App\InfantryPosition;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Setting;

class UserController extends Controller
{

    /**
     * @SWG\Get(
     *     tags={"Users"},
     *     path="/users",
     *     summary="Finds all users",
     *     description="Returns all users",
     *     @SWG\Response(
     *         response=200,
     *         description="A list of all users"
     *     )
     * )
     */
    public function fetch()
    {

        $users = DB::table('users')
                    ->select(
                        'users.name',
                        'users.email',
                        'users.admin',
                        'users.id'
                    )
                    ->orderBy('name')
                    ->get();


        return $users;
    }



    /**
     * @SWG\Post(
     *     tags={"Users"},
     *     path="/user/delete/{userId}",
     *     summary="Delete user by Id",
     *     description="Delete user by Id",
     *     @SWG\Parameter(
     *         description="Id of mission to delete",
     *         in="path",
     *         name="userId",
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

    if($request->user()->id == $id) {
        return response()->json(['error' => "You can't delete yourself, that would be suicide."], 403);
    }

        $user = DB::table('users')
                    ->delete($id);

        if($user) {
            return response()->json();
        } else {
            return response()->json(['error' => 'Not Found'], 404);
        }
    }



    /**
     * @SWG\Post(
     *     tags={"Users"},
     *     path="/user/delete/{userId}",
     *     summary="Delete user by Id",
     *     description="Delete user by Id",
     *     @SWG\Parameter(
     *         description="Id of mission to delete",
     *         in="path",
     *         name="userId",
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
    public function toggleAdmin(Request $request, $id)
    {
        if(!$request->user()->admin) {
            return response()->json(['error' => 'Not Allowed'], 403);
        }
        if($request->user()->id == $id) {
            return response()->json(['error' => "You can't remove your own admin"], 403);
        }

        $existingAdmin = DB::table('users')->where('id', $id)->select( 'users.admin')->get()[0]->admin;

        echo $existingAdmin;
        $user = DB::table('users')
            ->where('id', $id)
            ->update(['admin' => !$existingAdmin]);
        if($user) {
            return response()->json();
        } else {
            return response()->json(['error' => 'Not Found'], 404);
        }
    }

}
