<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Multi Pay Keysend response implementation.
 */
class MultiPayKeysendResponse extends AbstractResponse
{
    /**
     * Get all keysend payment results.
     *
     * @return array
     */
    public function getKeysends(): array
    {
        if ($this->isError()) {
            return [];
        }
        return $this->getResultField('keysends', []);
    }

    /**
     * Get the number of keysend payments.
     *
     * @return int
     */
    public function getKeysendCount(): int
    {
        return count($this->getKeysends());
    }

    /**
     * Get successful keysend payments.
     *
     * @return array
     */
    public function getSuccessfulKeysends(): array
    {
        if ($this->isError()) {
            return [];
        }
        return array_filter($this->getKeysends(), function ($keysend) {
            return !empty($keysend['preimage']);
        });
    }

    /**
     * Get failed keysend payments.
     *
     * @return array
     */
    public function getFailedKeysends(): array
    {
        if ($this->isError()) {
            return [];
        }
        return array_filter($this->getKeysends(), function ($keysend) {
            return empty($keysend['preimage']) || isset($keysend['error']);
        });
    }

    /**
     * Get the number of successful keysend payments.
     *
     * @return int
     */
    public function getSuccessfulKeysendCount(): int
    {
        return count($this->getSuccessfulKeysends());
    }

    /**
     * Get the number of failed keysend payments.
     *
     * @return int
     */
    public function getFailedKeysendCount(): int
    {
        return count($this->getFailedKeysends());
    }

    /**
     * Get total amount paid in millisatoshis (successful payments only).
     *
     * @return int
     */
    public function getTotalAmountPaid(): int
    {
        $total = 0;
        foreach ($this->getSuccessfulKeysends() as $keysend) {
            $total += $keysend['amount'] ?? 0;
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
        foreach ($this->getSuccessfulKeysends() as $keysend) {
            $total += $keysend['fees_paid'] ?? 0;
        }
        return $total;
    }

    /**
     * Check if all keysend payments were successful.
     *
     * @return bool
     */
    public function areAllKeysendsSuccessful(): bool
    {
        if ($this->isError()) {
            return false;
        }
        return $this->getFailedKeysendCount() === 0 && $this->getKeysendCount() > 0;
    }

    /**
     * Check if any keysend payments were successful.
     *
     * @return bool
     */
    public function hasSuccessfulKeysends(): bool
    {
        if ($this->isError()) {
            return false;
        }
        return $this->getSuccessfulKeysendCount() > 0;
    }

    /**
     * Get keysend payment by index.
     *
     * @param int $index
     * @return array|null
     */
    public function getKeysendByIndex(int $index): ?array
    {
        $keysends = $this->getKeysends();
        return $keysends[$index] ?? null;
    }

    /**
     * Get keysends by destination.
     *
     * @param string $destination
     * @return array
     */
    public function getKeysendsByDestination(string $destination): array
    {
        return array_filter($this->getKeysends(), function ($keysend) use ($destination) {
            return ($keysend['destination'] ?? '') === $destination;
        });
    }
}
