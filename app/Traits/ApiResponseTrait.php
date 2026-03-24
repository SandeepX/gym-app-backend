<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

trait ApiResponseTrait
{
    public function success($data, $message = 'Success', $code = ResponseAlias::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => 'success',
            'code' => $code,
            'message' => $message,
        ];

        if ($data instanceof ResourceCollection) {
            $response['data'] = isset($data->response()->getData(true)['data']) ? $data->response()->getData(true)['data'] : false;
            $response['links'] = isset($data->response()->getData(true)['links']) ? $data->response()->getData(true)['links'] : false;
            $response['meta'] = isset($data->response()->getData(true)['meta']) ? $data->response()->getData(true)['meta'] : false;
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    public function error($message = 'Error', $code = ResponseAlias::HTTP_BAD_REQUEST, $errors = []): JsonResponse
    {
        $errorMessage = [
            'url' => request()->path(),
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($errorMessage, $code);
    }
}
