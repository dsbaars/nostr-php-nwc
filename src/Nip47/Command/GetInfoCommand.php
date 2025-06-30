<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

/**
 * Get Info command implementation.
 *
 * Requests wallet information and supported methods.
 */
class GetInfoCommand extends AbstractCommand
{
    /**
     * Create a new get info command.
     */
    public function __construct()
    {
        // No parameters required for get_info
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'get_info';
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        // No validation needed for get_info
        return true;
    }
}
