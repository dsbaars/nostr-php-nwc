<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Exception;

/**
 * Exception thrown when an invalid NWC URI is encountered.
 */
class InvalidUriException extends NwcException
{
    public function __construct(string $message = "Invalid NWC URI format", int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
