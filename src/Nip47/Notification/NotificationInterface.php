<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Notification;

/**
 * Interface for NWC notifications.
 */
interface NotificationInterface
{
    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Convert notification to array for JSON encoding.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Create notification from array data.
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static;
}
