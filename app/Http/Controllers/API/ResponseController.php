<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ResponseController extends Controller
{
    protected function respond($data, $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }

    protected function respondSuccess($data, $statusCode = 200)
    {
        return $this->respond([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }

    protected function respondError($message, $statusCode, $errors = '')
    {
        return $this->respond([
            'success' => false,
            'error' => [
                'message' => $message,
                'status' => $statusCode,
                'errors' => $errors
            ]
        ], $statusCode);
    }

    protected function respondUnauthorized($message = 'Unauthorized')
    {
        return $this->respondError($message, 401);
    }

    protected function respondForbidden($message = 'Forbidden')
    {
        return $this->respondError($message, 403);
    }

    protected function respondNotFound($message = 'Not Found')
    {
        return $this->respondError($message, 404);
    }

    protected function respondUnprocessableEntity($message = 'Unprocessable Entity', $errors = '')
    {
        return $this->respondError($message, 422, $errors);
    }
}
