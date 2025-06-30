<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47;

/**
 * NIP-47 error codes as defined in the specification.
 */
enum ErrorCode: string
{
    case RATE_LIMITED = 'RATE_LIMITED';
    case NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    case QUOTA_EXCEEDED = 'QUOTA_EXCEEDED';
    case RESTRICTED = 'RESTRICTED';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case INTERNAL = 'INTERNAL';
    case OTHER = 'OTHER';
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case NOT_FOUND = 'NOT_FOUND';

    /**
     * Get error message for the error code.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::RATE_LIMITED => 'The client is sending commands too fast. It should retry in a few seconds.',
            self::NOT_IMPLEMENTED => 'The command is not known or is intentionally not implemented.',
            self::INSUFFICIENT_BALANCE => 'The wallet does not have enough funds to cover a fee reserve or the payment amount.',
            self::QUOTA_EXCEEDED => 'The wallet has exceeded its spending quota.',
            self::RESTRICTED => 'This public key is not allowed to do this operation.',
            self::UNAUTHORIZED => 'This public key has no wallet connected.',
            self::INTERNAL => 'An internal error.',
            self::OTHER => 'Other error.',
            self::PAYMENT_FAILED => 'The payment failed. This may be due to a timeout, exhausting all routes, insufficient capacity or similar.',
            self::NOT_FOUND => 'The invoice could not be found by the given parameters.',
        };
    }

    /**
     * Create exception from error code.
     *
     * @param string $message Optional custom message
     * @return \dsbaars\nostr\Nip47\Exception\NwcException
     */
    public function createException(string $message = ''): Exception\NwcException
    {
        $errorMessage = empty($message) ? $this->getMessage() : $message;

        return match ($this) {
            self::PAYMENT_FAILED => new Exception\PaymentFailedException($errorMessage),
            self::INSUFFICIENT_BALANCE => new Exception\InsufficientBalanceException($errorMessage),
            self::UNAUTHORIZED => new Exception\UnauthorizedException($errorMessage),
            self::RATE_LIMITED => new Exception\RateLimitedException($errorMessage),
            default => new Exception\CommandException($errorMessage),
        };
    }
}
