<?php

namespace App\Http\Controllers\api\auth;


trait apiResponse
{
    public function apiResponse($data=null, $message='')
    {
       return response()->json([
           'success' => true,
           'message' => $message,
           'data'    => $data,
           'errors' => [],
       ], 200);
    }

    public function sendError($error, int $statusCode = 401)
    {
    	$response = [
            'success' => false,
            'message' => $error,
            'data' => null,
            'errors' => [],
        ];

        return response()->json($response, $statusCode);
    }
}
