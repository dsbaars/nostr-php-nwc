<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Multi Pay Invoice response implementation.
 */
class MultiPayInvoiceResponse extends AbstractResponse
{
    /**
     * Get all payment results.
     *
     * @return array
     */
    public function getPayments(): array
    {
        if ($this->isError()) {
            return [];
        }
        return $this->getResultField('payments', []);
    }

    /**
     * Get the number of payments.
     *
     * @return int
     */
    public function getPaymentCount(): int
    {
        return count($this->getPayments());
    }

    /**
     * Get successful payments.
     *
     * @return array
     */
    public function getSuccessfulPayments(): array
    {
        if ($this->isError()) {
            return [];
        }
        return array_filter($this->getPayments(), function ($payment) {
            return !empty($payment['preimage']);
        });
    }

    /**
     * Get failed payments.
     *
     * @return array
     */
    public function getFailedPayments(): array
    {
        if ($this->isError()) {
            return [];
        }
        return array_filter($this->getPayments(), function ($payment) {
            return empty($payment['preimage']) || isset($payment['error']);
        });
    }

    /**
     * Get the number of successful payments.
     *
     * @return int
     */
    public function getSuccessfulPaymentCount(): int
    {
        return count($this->getSuccessfulPayments());
    }

    /**
     * Get the number of failed payments.
     *
     * @return int
     */
    public function getFailedPaymentCount(): int
    {
        return count($this->getFailedPayments());
    }

    /**
     * Get total amount paid in millisatoshis (successful payments only).
     *
     * @return int
     */
    public function getTotalAmountPaid(): int
    {
        $total = 0;
        foreach ($this->getSuccessfulPayments() as $payment) {
            $total += $payment['amount'] ?? 0;
        }
        return $total;
    }

    /**
     * Get total fees paid in millisatoshis.
     *
     * @return int
     */
    public function getTotalFeesPaid(): int
    {
        $total = 0;
        foreach ($this->getSuccessfulPayments() as $payment) {
            $total += $payment['fees_paid'] ?? 0;
        }
        return $total;
    }

    /**
     * Check if all payments were successful.
     *
     * @return bool
     */
    public function areAllPaymentsSuccessful(): bool
    {
        if ($this->isError()) {
            return false;
        }
        return $this->getFailedPaymentCount() === 0 && $this->getPaymentCount() > 0;
    }

    /**
     * Check if any payments were successful.
     *
     * @return bool
     */
    public function hasSuccessfulPayments(): bool
    {
        if ($this->isError()) {
            return false;
        }
        return $this->getSuccessfulPaymentCount() > 0;
    }

    /**
     * Get payment by index.
     *
     * @param int $index
     * @return array|null
     */
    public function getPaymentByIndex(int $index): ?array
    {
        $payments = $this->getPayments();
        return $payments[$index] ?? null;
    }
}
