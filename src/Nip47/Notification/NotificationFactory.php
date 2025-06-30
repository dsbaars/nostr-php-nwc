<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Notification;

/**
 * Factory for creating notification objects.
 */
class NotificationFactory
{
    /**
     * Create a notification from array data.
     *
     * @param array $data Notification data
     * @return NotificationInterface
     * @throws \InvalidArgumentException If notification type is unknown
     */
    public static function fromArray(array $data): NotificationInterface
    {
        $type = $data['notification_type'] ?? '';

        return match ($type) {
            PaymentReceivedNotification::TYPE => PaymentReceivedNotification::fromArray($data),
            PaymentSentNotification::TYPE => PaymentSentNotification::fromArray($data),
            default => throw new \InvalidArgumentException("Unknown notification type: {$type}"),
        };
    }

    /**
     * Create a payment received notification.
     *
     * @param string $paymentHash
     * @param int $amount
     * @param string|null $description
     * @param string|null $descriptionHash
     * @param string|null $preimage
     * @param int|null $settledAt
     * @param array|null $metadata
     * @return PaymentReceivedNotification
     */
    public static function createPaymentReceived(
        string $paymentHash,
        int $amount,
        ?string $description = null,
        ?string $descriptionHash = null,
        ?string $preimage = null,
        ?int $settledAt = null,
        ?array $metadata = null,
    ): PaymentReceivedNotification {
        return new PaymentReceivedNotification(
            paymentHash: $paymentHash,
            amount: $amount,
            description: $description,
            descriptionHash: $descriptionHash,
            preimage: $preimage,
            settledAt: $settledAt,
            metadata: $metadata,
        );
    }

    /**
     * Create a payment sent notification.
     *
     * @param string $paymentHash
     * @param int $amount
     * @param int $feesPaid
     * @param string|null $description
     * @param string|null $descriptionHash
     * @param string|null $preimage
     * @param string|null $invoice
     * @param int|null $settledAt
     * @param array|null $metadata
     * @return PaymentSentNotification
     */
    public static function createPaymentSent(
        string $paymentHash,
        int $amount,
        int $feesPaid = 0,
        ?string $description = null,
        ?string $descriptionHash = null,
        ?string $preimage = null,
        ?string $invoice = null,
        ?int $settledAt = null,
        ?array $metadata = null,
    ): PaymentSentNotification {
        return new PaymentSentNotification(
            paymentHash: $paymentHash,
            amount: $amount,
            feesPaid: $feesPaid,
            description: $description,
            descriptionHash: $descriptionHash,
            preimage: $preimage,
            invoice: $invoice,
            settledAt: $settledAt,
            metadata: $metadata,
        );
    }

    /**
     * Get all supported notification types.
     *
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return [
            PaymentReceivedNotification::TYPE,
            PaymentSentNotification::TYPE,
        ];
    }
}
