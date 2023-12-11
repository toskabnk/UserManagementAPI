<?php

namespace App\Http\Controllers\API;

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
            'data' => [
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

    protected function respondNotFound($message = 'Not Found', $errors = '')
    {
        return $this->respondError($message, 404, $errors);
    }

    protected function respondUnprocessableEntity($message = 'Unprocessable Entity', $errors = '')
    {
        return $this->respondError($message, 422, $errors);
    }

    protected function respondInternalServerError($message = 'Internal Server Error', $errors = '')
    {
        return $this->respondError($message, 500, $errors);
    }

    protected function respondInternalError($message = 'Internal Server Error', $errors = '')
    {
        return $this->respondError($message, 500, $errors);
    }
}
