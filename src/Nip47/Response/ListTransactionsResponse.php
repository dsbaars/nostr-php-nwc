<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * List Transactions response implementation.
 */
class ListTransactionsResponse extends AbstractResponse
{
    /**
     * Get all transactions.
     *
     * @return array
     */
    public function getTransactions(): array
    {
        if ($this->isError()) {
            return [];
        }
        return $this->getResultField('transactions', []);
    }

    /**
     * Get the number of transactions returned.
     *
     * @return int
     */
    public function getTransactionCount(): int
    {
        return count($this->getTransactions());
    }

    /**
     * Get transactions filtered by type.
     *
     * @param string $type "incoming" or "outgoing"
     * @return array
     */
    public function getTransactionsByType(string $type): array
    {
        return array_filter($this->getTransactions(), function ($transaction) use ($type) {
            return ($transaction['type'] ?? '') === $type;
        });
    }

    /**
     * Get incoming transactions.
     *
     * @return array
     */
    public function getIncomingTransactions(): array
    {
        return $this->getTransactionsByType('incoming');
    }

    /**
     * Get outgoing transactions.
     *
     * @return array
     */
    public function getOutgoingTransactions(): array
    {
        return $this->getTransactionsByType('outgoing');
    }

    /**
     * Get settled transactions.
     *
     * @return array
     */
    public function getSettledTransactions(): array
    {
        return array_filter($this->getTransactions(), function ($transaction) {
            return isset($transaction['settled_at']) && $transaction['settled_at'] !== null;
        });
    }

    /**
     * Get unsettled transactions.
     *
     * @return array
     */
    public function getUnsettledTransactions(): array
    {
        return array_filter($this->getTransactions(), function ($transaction) {
            return !isset($transaction['settled_at']) || $transaction['settled_at'] === null;
        });
    }

    /**
     * Get total amount of all transactions in millisatoshis.
     *
     * @return int
     */
    public function getTotalAmount(): int
    {
        $total = 0;
        foreach ($this->getTransactions() as $transaction) {
            $total += $transaction['amount'] ?? 0;
        }
        return $total;
    }

    /**
     * Get total fees paid in millisatoshis.
     *
     * @return int
     */
    public function getTotalFees(): int
    {
        $total = 0;
        foreach ($this->getTransactions() as $transaction) {
            $total += $transaction['fees_paid'] ?? 0;
        }
        return $total;
    }

    /**
     * Check if there are any transactions.
     *
     * @return bool
     */
    public function hasTransactions(): bool
    {
        return $this->getTransactionCount() > 0;
    }
}
