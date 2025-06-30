<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Exception;

/**
 * Exception thrown when client is not authorized.
 */
class UnauthorizedException extends NwcException
{
    public function __construct(string $message = "Unauthorized", int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
