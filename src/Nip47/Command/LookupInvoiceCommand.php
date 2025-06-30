<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Lookup Invoice command implementation.
 *
 * Requests lookup of an invoice by payment hash or invoice string.
 */
class LookupInvoiceCommand extends AbstractCommand
{
    /**
     * Create a new lookup invoice command.
     *
     * @param string|null $paymentHash Payment hash of the invoice
     * @param string|null $invoice Invoice string to lookup
     */
    public function __construct(?string $paymentHash = null, ?string $invoice = null)
    {
        if ($paymentHash !== null) {
            $this->setParam('payment_hash', $paymentHash);
        }

        if ($invoice !== null) {
            $this->setParam('invoice', $invoice);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'lookup_invoice';
    }

    /**
     * Get the payment hash.
     *
     * @return string|null
     */
    public function getPaymentHash(): ?string
    {
        return $this->getParam('payment_hash');
    }

    /**
     * Get the invoice string.
     *
     * @return string|null
     */
    public function getInvoice(): ?string
    {
        return $this->getParam('invoice');
    }

    /**
     * Set the payment hash.
     *
     * @param string $paymentHash
     * @return static
     */
    public function setPaymentHash(string $paymentHash): static
    {
        return $this->setParam('payment_hash', $paymentHash);
    }

    /**
     * Set the invoice string.
     *
     * @param string $invoice
     * @return static
     */
    public function setInvoice(string $invoice): static
    {
        return $this->setParam('invoice', $invoice);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $paymentHash = $this->getPaymentHash();
        $invoice = $this->getInvoice();

        // Either payment_hash or invoice is required
        if ($paymentHash === null && $invoice === null) {
            throw new CommandException("Either payment_hash or invoice is required");
        }

        // Validate payment hash format if provided
        if ($paymentHash !== null && !preg_match('/^[a-f0-9]{64}$/i', $paymentHash)) {
            throw new CommandException("Invalid payment hash format");
        }

        // Validate invoice format if provided
        if ($invoice !== null && !str_starts_with(strtolower($invoice), 'lnbc') && !str_starts_with(strtolower($invoice), 'lntb')) {
            throw new CommandException("Invalid invoice format");
        }

        return true;
    }
}
