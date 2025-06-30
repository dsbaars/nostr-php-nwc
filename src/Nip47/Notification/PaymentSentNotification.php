<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Notification;

/**
 * Payment sent notification.
 *
 * Sent when a payment is sent from the wallet.
 */
class PaymentSentNotification implements NotificationInterface
{
    public const TYPE = 'payment_sent';

    /**
     * @param string $paymentHash The payment hash
     * @param int $amount Amount sent in millisatoshis
     * @param int $feesPaid Fees paid in millisatoshis
     * @param string|null $description Payment description if available
     * @param string|null $descriptionHash Payment description hash if available
     * @param string|null $preimage Payment preimage if available
     * @param string|null $invoice Lightning invoice that was paid
     * @param int|null $settledAt Unix timestamp when payment was settled
     * @param array|null $metadata Additional payment metadata
     */
    public function __construct(
        private string $paymentHash,
        private int $amount,
        private int $feesPaid = 0,
        private ?string $description = null,
        private ?string $descriptionHash = null,
        private ?string $preimage = null,
        private ?string $invoice = null,
        private ?int $settledAt = null,
        private ?array $metadata = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * Get payment hash.
     *
     * @return string
     */
    public function getPaymentHash(): string
    {
        return $this->paymentHash;
    }

    /**
     * Get amount in millisatoshis.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get amount in satoshis.
     *
     * @return int
     */
    public function getAmountInSats(): int
    {
        return intval($this->amount / 1000);
    }

    /**
     * Get fees paid in millisatoshis.
     *
     * @return int
     */
    public function getFeesPaid(): int
    {
        return $this->feesPaid;
    }

    /**
     * Get fees paid in satoshis.
     *
     * @return int
     */
    public function getFeesPaidInSats(): int
    {
        return intval($this->feesPaid / 1000);
    }

    /**
     * Get payment description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get payment description hash.
     *
     * @return string|null
     */
    public function getDescriptionHash(): ?string
    {
        return $this->descriptionHash;
    }

    /**
     * Get payment preimage.
     *
     * @return string|null
     */
    public function getPreimage(): ?string
    {
        return $this->preimage;
    }

    /**
     * Get lightning invoice.
     *
     * @return string|null
     */
    public function getInvoice(): ?string
    {
        return $this->invoice;
    }

    /**
     * Get settled timestamp.
     *
     * @return int|null
     */
    public function getSettledAt(): ?int
    {
        return $this->settledAt;
    }

    /**
     * Get payment metadata.
     *
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'notification_type' => $this->getType(),
            'notification' => array_filter([
                'payment_hash' => $this->paymentHash,
                'amount' => $this->amount,
                'fees_paid' => $this->feesPaid,
                'description' => $this->description,
                'description_hash' => $this->descriptionHash,
                'preimage' => $this->preimage,
                'invoice' => $this->invoice,
                'settled_at' => $this->settledAt,
                'metadata' => $this->metadata,
            ], fn($value) => $value !== null),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): static
    {
        $notification = $data['notification'] ?? [];

        return new static(
            paymentHash: $notification['payment_hash'] ?? '',
            amount: $notification['amount'] ?? 0,
            feesPaid: $notification['fees_paid'] ?? 0,
            description: $notification['description'] ?? null,
            descriptionHash: $notification['description_hash'] ?? null,
            preimage: $notification['preimage'] ?? null,
            invoice: $notification['invoice'] ?? null,
            settledAt: $notification['settled_at'] ?? null,
            metadata: $notification['metadata'] ?? null,
        );
    }
}
