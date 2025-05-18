<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Classes\FireBaseServices\FirebaseService;
// use App\Repository\Models\Notification\NotificationService;

class NotiController extends Controller
{
    // private $repo;
    // public function __construct()
    // {
    //     $FirebaseService = new FirebaseService();
    //     $this->repo = new NotificationService($FirebaseService);
    // }
    // public function saveFCT(Request $request)
    // {
    //     return $this->repo->saveFCT($request);
    // }

    public function getNotifications()
    {

        $user = auth()->user();

        $notifications = $user->notifications;

        return response()->json([
            'data' => $notifications
        ]);
    }

    public function deleteNotification($id)
    {
        $user = auth()->user()->notifications()->findOrFail($id)->delete();
        return response()->json([
            'data' => 'done'
        ]);
    }
}
