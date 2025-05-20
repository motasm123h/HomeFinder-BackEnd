<?php

namespace App\Http\Controllers;
use App\Models\Reviews;

use Illuminate\Http\Request;

class CommonController extends Controller
{
    public function create(Request $request){
        $atter = $request->validate([
            'name' =>['required'],
            'phone' =>['required'],
            'descripition' =>['required'],
        ]);

        $com = Reviews::create($atter);
        return response()->json([
            'data'=>$com,
        ]);
    }

    public function index(){
        $com = Reviews::paginate(12);

        return response()->json([
            'data'=>$com,
        ]);
    }
}
