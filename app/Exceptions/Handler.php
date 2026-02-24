<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // 1) Validation errors -> keep your current JSON format
        if ($request->expectsJson() && $e instanceof ValidationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $e->errors(), // field => [messages]
            ], 422);
        }

        // 2) abort(403/404/...) -> show meaningful JSON instead of empty message
        if ($request->expectsJson() && $e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $msg = trim((string) $e->getMessage());

            // If abort() was called without a message, provide a default
            if ($msg === '') {
                $msg = match ($status) {
                    401 => 'Unauthenticated.',
                    403 => 'Forbidden.',
                    404 => 'Not Found.',
                    419 => 'Page Expired.',
                    429 => 'Too Many Requests.',
                    default => "HTTP {$status}",
                };
            }

            return response()->json([
                'status'  => 'error',
                'message' => $msg,
            ], $status);
        }

        return parent::render($request, $e);
    }
}
