<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Exception;

/**
 * Exception thrown when wallet has insufficient balance.
 */
class InsufficientBalanceException extends NwcException
{
    public function __construct(string $message = "Insufficient balance", int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
