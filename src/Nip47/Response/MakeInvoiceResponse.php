<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Make Invoice response implementation.
 */
class MakeInvoiceResponse extends AbstractResponse
{
    /**
     * Get the transaction type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getResultField('type');
    }

    /**
     * Get the bolt11 invoice string.
     *
     * @return string|null
     */
    public function getInvoice(): ?string
    {
        return $this->getResultField('invoice');
    }

    /**
     * Get the invoice description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getResultField('description');
    }

    /**
     * Get the invoice description hash.
     *
     * @return string|null
     */
    public function getDescriptionHash(): ?string
    {
        return $this->getResultField('description_hash');
    }

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
     * Get the amount in millisatoshis.
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->getResultField('amount');
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
     * Get the creation timestamp.
     *
     * @return int|null
     */
    public function getCreatedAt(): ?int
    {
        return $this->getResultField('created_at');
    }

    /**
     * Get the expiration timestamp.
     *
     * @return int|null
     */
    public function getExpiresAt(): ?int
    {
        return $this->getResultField('expires_at');
    }

    /**
     * Get the metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->getResultField('metadata', []);
    }

    /**
     * Check if this is an incoming transaction (invoice).
     *
     * @return bool
     */
    public function isIncoming(): bool
    {
        return $this->getType() === 'incoming';
    }

    /**
     * Check if the invoice has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        return $expiresAt !== null && time() > $expiresAt;
    }
}
