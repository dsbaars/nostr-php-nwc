<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Pay Keysend response implementation.
 */
class PayKeysendResponse extends AbstractResponse
{
    /**
     * Get the payment preimage.
     *
     * @return string|null
     */
    public function getPreimage(): ?string
    {
        return $this->getResultField('preimage');
    }

    /**
     * Get the payment hash.
     *
     * @return string|null
     */
    public function getPaymentHash(): ?string
    {
        return $this->getResultField('payment_hash');
    }

    /**
     * Get the fees paid in millisatoshis.
     *
     * @return int|null
     */
    public function getFeesPaid(): ?int
    {
        return $this->getResultField('fees_paid');
    }

    /**
     * Get the amount paid in millisatoshis.
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->getResultField('amount');
    }

    /**
     * Get the destination pubkey.
     *
     * @return string|null
     */
    public function getDestination(): ?string
    {
        return $this->getResultField('destination');
    }

    /**
     * Get the creation timestamp.
     *
     * @return int|null
     */
    public function getCreatedAt(): ?int
    {
        return $this->getResultField('created_at');
    }

    /**
     * Check if payment was successful (has preimage).
     *
     * @return bool
     */
    public function isPaymentSuccessful(): bool
    {
        return $this->isSuccess() && !empty($this->getPreimage());
    }
}
