<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UserNotFoundException extends Exception
{
    public function __construct(string $message = "User not found", int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
