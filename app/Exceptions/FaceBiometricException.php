<?php

namespace App\Exceptions;

use RuntimeException;

class FaceBiometricException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message ?: $reason, $code, $previous);
    }
}
