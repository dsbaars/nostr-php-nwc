<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Make Invoice command implementation.
 *
 * Requests creation of a Lightning invoice.
 */
class MakeInvoiceCommand extends AbstractCommand
{
    /**
     * Create a new make invoice command.
     *
     * @param int $amount Amount in millisatoshis
     * @param string|null $description Invoice description
     * @param string|null $descriptionHash Invoice description hash
     * @param int|null $expiry Expiry in seconds from creation time
     */
    public function __construct(int $amount, ?string $description = null, ?string $descriptionHash = null, ?int $expiry = null)
    {
        $this->setParam('amount', $amount);

        if ($description !== null) {
            $this->setParam('description', $description);
        }

        if ($descriptionHash !== null) {
            $this->setParam('description_hash', $descriptionHash);
        }

        if ($expiry !== null) {
            $this->setParam('expiry', $expiry);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'make_invoice';
    }

    /**
     * Get the amount in millisatoshis.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->getParam('amount');
    }

    /**
     * Get the invoice description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getParam('description');
    }

    /**
     * Get the invoice description hash.
     *
     * @return string|null
     */
    public function getDescriptionHash(): ?string
    {
        return $this->getParam('description_hash');
    }

    /**
     * Get the expiry in seconds.
     *
     * @return int|null
     */
    public function getExpiry(): ?int
    {
        return $this->getParam('expiry');
    }

    /**
     * Set the description.
     *
     * @param string $description
     * @return static
     */
    public function setDescription(string $description): static
    {
        return $this->setParam('description', $description);
    }

    /**
     * Set the description hash.
     *
     * @param string $descriptionHash
     * @return static
     */
    public function setDescriptionHash(string $descriptionHash): static
    {
        return $this->setParam('description_hash', $descriptionHash);
    }

    /**
     * Set the expiry in seconds.
     *
     * @param int $expiry
     * @return static
     */
    public function setExpiry(int $expiry): static
    {
        return $this->setParam('expiry', $expiry);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $amount = $this->getAmount();
        if ($amount <= 0) {
            throw new CommandException("Amount must be positive");
        }

        $expiry = $this->getExpiry();
        if ($expiry !== null && $expiry <= 0) {
            throw new CommandException("Expiry must be positive");
        }

        // Cannot have both description and description_hash
        if ($this->hasParam('description') && $this->hasParam('description_hash')) {
            throw new CommandException("Cannot specify both description and description_hash");
        }

        return true;
    }
}
