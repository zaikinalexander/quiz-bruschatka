<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

trait ResponseStatusTrait
{
    protected function success(array $data = [], ?string $message = null, int $code = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'result' => $data,
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $code);
    }

    protected function failed(array $errors = [], ?string $message = null, int $code = 400): JsonResponse
    {
        $payload = [
            'success' => false,
            'errors' => $errors,
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $code);
    }
}

