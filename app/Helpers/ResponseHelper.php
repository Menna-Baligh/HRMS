<?php

namespace App\Helpers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ResponseHelper
{
    public static function success(mixed $data = [], ?string $message = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('Response successful'),
            'data' => $data,
        ], $statusCode);
    }

    public static function error(mixed $errors = null, ?string $message = null, int $statusCode = 400): JsonResponse
    {
        $resolvedMessage = $message;

        if (! empty($errors)) {
            if ($errors instanceof MessageProvider) {
                $errorMessages = $errors->getMessageBag()->all();
            } elseif ($errors instanceof Arrayable) {
                $errorMessages = Arr::flatten($errors->toArray());
            } elseif (is_array($errors)) {
                $errorMessages = Arr::flatten($errors);
            } else {
                $errorMessages = [(string) $errors];
            }

            $errorMessages = array_filter(array_map('trim', $errorMessages));
            if (! empty($errorMessages)) {
                $resolvedMessage = implode(' ', $errorMessages);
            }
        }

        return response()->json([
            'success' => false,
            'message' => $resolvedMessage ?? __('An error occurred'),
        ], $statusCode);
    }
}
