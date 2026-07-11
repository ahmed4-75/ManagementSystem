<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', int $statusCode = 403)
    {
        parent::__construct($statusCode, $message);
    }

    public function render()
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getStatusCode());
    }
}
