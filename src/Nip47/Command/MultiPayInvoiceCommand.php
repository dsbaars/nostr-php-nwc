<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Multi Pay Invoice command implementation.
 *
 * Requests payment of multiple Lightning invoices.
 */
class MultiPayInvoiceCommand extends AbstractCommand
{
    /**
     * Create a new multi pay invoice command.
     *
     * @param array $invoices Array of invoice data, each containing 'invoice' and optionally 'amount'
     */
    public function __construct(array $invoices)
    {
        $this->setParam('invoices', $invoices);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'multi_pay_invoice';
    }

    /**
     * Get the invoices array.
     *
     * @return array
     */
    public function getInvoices(): array
    {
        return $this->getParam('invoices', []);
    }

    /**
     * Add an invoice to the list.
     *
     * @param string $invoice The bolt11 invoice
     * @param int|null $amount Optional amount in millisatoshis (for zero-amount invoices)
     * @return static
     */
    public function addInvoice(string $invoice, ?int $amount = null): static
    {
        $invoices = $this->getInvoices();
        $invoiceData = ['invoice' => $invoice];

        if ($amount !== null) {
            $invoiceData['amount'] = $amount;
        }

        $invoices[] = $invoiceData;
        return $this->setParam('invoices', $invoices);
    }

    /**
     * Set the invoices array.
     *
     * @param array $invoices
     * @return static
     */
    public function setInvoices(array $invoices): static
    {
        return $this->setParam('invoices', $invoices);
    }

    /**
     * Get the number of invoices.
     *
     * @return int
     */
    public function getInvoiceCount(): int
    {
        return count($this->getInvoices());
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $invoices = $this->getInvoices();

        if (empty($invoices)) {
            throw new CommandException("At least one invoice is required");
        }

        foreach ($invoices as $index => $invoiceData) {
            if (!is_array($invoiceData)) {
                throw new CommandException("Invoice data at index {$index} must be an array");
            }

            if (!isset($invoiceData['invoice']) || empty($invoiceData['invoice'])) {
                throw new CommandException("Invoice at index {$index} is required");
            }

            $invoice = $invoiceData['invoice'];
            if (!str_starts_with(strtolower($invoice), 'lnbc') && !str_starts_with(strtolower($invoice), 'lntb')) {
                throw new CommandException("Invalid invoice format at index {$index}");
            }

            if (isset($invoiceData['amount']) && $invoiceData['amount'] <= 0) {
                throw new CommandException("Amount must be positive at index {$index}");
            }
        }

        return true;
    }
}
