<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

/**
 * List Transactions command implementation.
 *
 * Requests a list of wallet transactions.
 */
class ListTransactionsCommand extends AbstractCommand
{
    /**
     * Create a new list transactions command.
     *
     * @param int|null $from Unix timestamp to filter transactions from (inclusive)
     * @param int|null $until Unix timestamp to filter transactions until (inclusive)
     * @param int|null $limit Maximum number of transactions to return
     * @param int|null $offset Number of transactions to skip
     * @param bool|null $unpaid If true, only return unpaid transactions
     * @param string|null $type Filter by transaction type ("incoming" or "outgoing")
     */
    public function __construct(
        ?int $from = null,
        ?int $until = null,
        ?int $limit = null,
        ?int $offset = null,
        ?bool $unpaid = null,
        ?string $type = null,
    ) {
        if ($from !== null) {
            $this->setParam('from', $from);
        }

        if ($until !== null) {
            $this->setParam('until', $until);
        }

        if ($limit !== null) {
            $this->setParam('limit', $limit);
        }

        if ($offset !== null) {
            $this->setParam('offset', $offset);
        }

        if ($unpaid !== null) {
            $this->setParam('unpaid', $unpaid);
        }

        if ($type !== null) {
            $this->setParam('type', $type);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'list_transactions';
    }

    /**
     * Get the from timestamp.
     *
     * @return int|null
     */
    public function getFrom(): ?int
    {
        return $this->getParam('from');
    }

    /**
     * Get the until timestamp.
     *
     * @return int|null
     */
    public function getUntil(): ?int
    {
        return $this->getParam('until');
    }

    /**
     * Get the limit.
     *
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->getParam('limit');
    }

    /**
     * Get the offset.
     *
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->getParam('offset');
    }

    /**
     * Get the unpaid filter.
     *
     * @return bool|null
     */
    public function getUnpaid(): ?bool
    {
        return $this->getParam('unpaid');
    }

    /**
     * Get the transaction type filter.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getParam('type');
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        // All parameters are optional for list_transactions
        return true;
    }
}
