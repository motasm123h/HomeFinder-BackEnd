<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ResourceConflictException extends Exception
{
    public function __construct(string $message = 'Cannot delete resource due to existing relations', int $code = 409, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
