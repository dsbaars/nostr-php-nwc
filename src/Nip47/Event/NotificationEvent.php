<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;
use dsbaars\nostr\Nip47\Notification\NotificationInterface;
use swentel\nostr\Encryption\Nip04;

/**
 * NWC Notification Event (kind 23196).
 *
 * Encrypted notifications sent from wallet services to clients.
 */
class NotificationEvent extends Event
{
    public const KIND = 23196;

    /**
     * Create a new notification event.
     *
     * @param NotificationInterface $notification The notification to send
     * @param string $walletPrivkey Wallet service private key
     * @param string $clientPubkey Client's public key
     */
    public function __construct(NotificationInterface $notification, string $walletPrivkey, string $clientPubkey)
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Add p tag for client
        $this->addTag(['p', $clientPubkey]);

        // Add notification type tag
        $this->addTag(['notification_type', $notification->getType()]);

        // Encrypt the notification using NIP-04
        $notificationJson = json_encode($notification->toArray());
        $encryptedContent = Nip04::encrypt($notificationJson, $walletPrivkey, $clientPubkey);
        $this->setContent($encryptedContent);
    }

    /**
     * Create notification event from notification object.
     *
     * @param NotificationInterface $notification
     * @param string $walletPrivkey
     * @param string $clientPubkey
     * @return static
     */
    public static function fromNotification(NotificationInterface $notification, string $walletPrivkey, string $clientPubkey): static
    {
        return new static($notification, $walletPrivkey, $clientPubkey);
    }

    /**
     * Decrypt and parse the notification from this event.
     *
     * @param string $privateKey The private key to decrypt with
     * @param string $publicKey The public key to decrypt with
     * @return array The decrypted notification data
     * @throws \Exception
     */
    public function decryptNotification(string $privateKey, string $publicKey): array
    {
        $decrypted = Nip04::decrypt($this->getContent(), $privateKey, $publicKey);
        return json_decode($decrypted, true);
    }

    /**
     * Get the notification type from tags.
     *
     * @return string|null
     */
    public function getNotificationType(): ?string
    {
        foreach ($this->getTags() as $tag) {
            if ($tag[0] === 'notification_type' && isset($tag[1])) {
                return $tag[1];
            }
        }
        return null;
    }
}
