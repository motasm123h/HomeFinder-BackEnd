<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class OfficeDataFormattingException extends Exception
{
    public function __construct(string $message = 'Failed to format office data', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
