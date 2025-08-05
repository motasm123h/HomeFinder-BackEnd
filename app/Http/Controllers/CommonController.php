<?php

namespace App\Http\Controllers;

use App\Models\Reviews;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    use ResponseTrait;

    public function create(Request $request)
    {

        $atter = $request->validate([
            'name' => ['required'],
            'phone' => ['required'],
            'descripition' => ['required'],
            'user_id' => ['required'],
        ]);

        $com = Reviews::create($atter);
        return $this->apiResponse(
            'Data created successfully',
            $com,
            201
        );
    }

    public function seen(int $review_id)
    {
        $review = Reviews::where('id', $review_id)->first()->update(['seen' => 'yes']);
        return response()->json([
            'data' => $review,
        ]);
    }
    public function getReviewsByOffice(int $user_id)
    {
        $reviews = User::where('id', $user_id)->first()->with('reviews')->get();
        return response()->json([
            'data' => $reviews,
        ]);
    }

    public function index()
    {
        $com = Reviews::paginate(12);
        return $this->apiResponse(
            'Data retrieved successfully',
            $com,
            200
        );
    }

    // In CommonController.php
    public function delete(int $id)
    {
        $data = Reviews::where('id', $id)->first();
        return $this->apiResponse(
            $data->delete(),
            null,
            201
        );
    }
}
