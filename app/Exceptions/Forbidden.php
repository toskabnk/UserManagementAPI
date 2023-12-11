<?php

namespace App\Exceptions;

use Exception;

class Forbidden extends Exception
{
    public function __construct($message = 'Foribdden', $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
