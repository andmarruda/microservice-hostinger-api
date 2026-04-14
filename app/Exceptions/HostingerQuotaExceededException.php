<?php

namespace App\Exceptions;

class HostingerQuotaExceededException extends \RuntimeException
{
    public function __construct(string $message = 'Hostinger API daily quota exceeded.')
    {
        parent::__construct($message, 503);
    }
}
