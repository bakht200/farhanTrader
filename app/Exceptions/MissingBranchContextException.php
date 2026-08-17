<?php

namespace App\Exceptions;

use RuntimeException;

class MissingBranchContextException extends RuntimeException
{
    public function __construct(string $message = 'No active branch context is available.')
    {
        parent::__construct($message);
    }
}
