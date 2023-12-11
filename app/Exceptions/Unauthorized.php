<?php

namespace App\Exceptions;

use Exception;

class Unauthorized extends Exception
{
    public function __construct($message = 'Unauthorized', $code = 401, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
