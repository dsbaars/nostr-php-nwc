<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Get Balance response implementation.
 */
class GetBalanceResponse extends AbstractResponse
{
    /**
     * Get the wallet balance in millisatoshis.
     *
     * @return int|null
     */
    public function getBalance(): ?int
    {
        return $this->getResultField('balance');
    }

    /**
     * Get the balance in satoshis.
     *
     * @return float|null
     */
    public function getBalanceInSats(): ?float
    {
        $balance = $this->getBalance();
        return $balance !== null ? $balance / 1000 : null;
    }

    /**
     * Get the balance in bitcoins.
     *
     * @return float|null
     */
    public function getBalanceInBtc(): ?float
    {
        $balance = $this->getBalance();
        return $balance !== null ? $balance / 100000000000 : null;
    }
}
