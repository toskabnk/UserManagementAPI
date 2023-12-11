<?php

namespace App\Exceptions;

use Exception;

class NotFound extends Exception
{
    public function __construct($message = 'Not found', $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
