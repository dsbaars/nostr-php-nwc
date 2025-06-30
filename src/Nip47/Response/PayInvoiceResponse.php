<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Pay Invoice response implementation.
 */
class PayInvoiceResponse extends AbstractResponse
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
     * Get the fees paid in millisatoshis.
     *
     * @return int|null
     */
    public function getFeesPaid(): ?int
    {
        return $this->getResultField('fees_paid');
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
