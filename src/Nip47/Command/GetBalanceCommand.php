<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

/**
 * Get Balance command implementation.
 *
 * Requests the current wallet balance.
 */
class GetBalanceCommand extends AbstractCommand
{
    /**
     * Create a new get balance command.
     */
    public function __construct()
    {
        // No parameters required for get_balance
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'get_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        // No validation needed for get_balance
        return true;
    }
}
