<?php

namespace App\Traits;

trait ResponseTrait
{
    public function apiResponse($message = null, $data = null, $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
