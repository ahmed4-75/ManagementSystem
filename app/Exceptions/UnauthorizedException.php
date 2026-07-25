<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', int $statusCode = 403)
    {
        parent::__construct($statusCode, $message);
    }

    public function render()
    {
        Log::error($this->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => config('app.debug') || app()->runningUnitTests()
                ? $this->getMessage()
                : 'An unexpected error occurred.',
        ], $this->getStatusCode());
    }
}
