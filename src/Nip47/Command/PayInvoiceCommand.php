<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Pay Invoice command implementation.
 *
 * Requests payment of a Lightning invoice.
 */
class PayInvoiceCommand extends AbstractCommand
{
    /**
     * Create a new pay invoice command.
     *
     * @param string $invoice The bolt11 invoice to pay
     * @param int|null $amount Optional amount in millisatoshis (for zero-amount invoices)
     */
    public function __construct(string $invoice, ?int $amount = null)
    {
        $this->setParam('invoice', $invoice);

        if ($amount !== null) {
            $this->setParam('amount', $amount);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'pay_invoice';
    }

    /**
     * Get the invoice to pay.
     *
     * @return string
     */
    public function getInvoice(): string
    {
        return $this->getParam('invoice');
    }

    /**
     * Get the amount in millisatoshis.
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->getParam('amount');
    }

    /**
     * Set the amount in millisatoshis.
     *
     * @param int $amount
     * @return static
     */
    public function setAmount(int $amount): static
    {
        return $this->setParam('amount', $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $invoice = $this->getInvoice();

        if (empty($invoice)) {
            throw new CommandException("Invoice is required");
        }

        if (!str_starts_with(strtolower($invoice), 'lnbc') && !str_starts_with(strtolower($invoice), 'lntb')) {
            throw new CommandException("Invalid invoice format");
        }

        $amount = $this->getAmount();
        if ($amount !== null && $amount <= 0) {
            throw new CommandException("Amount must be positive");
        }

        return true;
    }
}
