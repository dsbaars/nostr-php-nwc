<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

/**
 * Interface for all NWC commands.
 */
interface CommandInterface
{
    /**
     * Get the command method name.
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Get the command parameters.
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Convert command to array representation for JSON-RPC.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Validate command parameters.
     *
     * @return bool
     * @throws \dsbaars\nostr\Nip47\Exception\CommandException
     */
    public function validate(): bool;
}
