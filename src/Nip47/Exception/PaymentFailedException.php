<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Exception;

/**
 * Exception thrown when a payment fails.
 */
class PaymentFailedException extends NwcException
{
    public function __construct(string $message = "Payment failed", int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
