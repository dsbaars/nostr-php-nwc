<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;

/**
 * NWC Info Event (kind 13194).
 *
 * Replaceable event published by wallet services to announce capabilities.
 */
class InfoEvent extends Event
{
    public const KIND = 13194;

    /**
     * Create a new info event.
     *
     * @param array $supportedMethods Array of supported command methods
     * @param array $supportedNotifications Array of supported notification types
     * @param array $supportedEncryption Array of supported encryption schemes
     */
    public function __construct(array $supportedMethods = [], array $supportedNotifications = [], array $supportedEncryption = [])
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Set content as space-separated list of supported methods
        $this->setContent(implode(' ', $supportedMethods));

        // Add notifications tag if notifications are supported
        if (!empty($supportedNotifications)) {
            $this->addTag(['notifications', implode(' ', $supportedNotifications)]);
        }

        // Add encryption tag if encryption schemes are supported
        if (!empty($supportedEncryption)) {
            $this->addTag(['encryption', implode(' ', $supportedEncryption)]);
        }
    }

    /**
     * Get supported methods from content.
     *
     * @return array
     */
    public function getSupportedMethods(): array
    {
        $content = trim($this->getContent());
        return empty($content) ? [] : explode(' ', $content);
    }

    /**
     * Get supported notifications from tags.
     *
     * @return array
     */
    public function getSupportedNotifications(): array
    {
        $tags = $this->getTags();
        foreach ($tags as $tag) {
            if (isset($tag[0]) && $tag[0] === 'notifications' && isset($tag[1])) {
                $notifications = $tag[1];
                return empty($notifications) ? [] : explode(' ', $notifications);
            }
        }
        return [];
    }

    /**
     * Get supported encryption schemes from tags.
     *
     * @return array
     */
    public function getSupportedEncryptions(): array
    {
        $tags = $this->getTags();
        foreach ($tags as $tag) {
            if (isset($tag[0]) && $tag[0] === 'encryption' && isset($tag[1])) {
                $encryption = $tag[1];
                return empty($encryption) ? [] : explode(' ', $encryption);
            }
        }
        return [];
    }

    /**
     * Check if a method is supported.
     *
     * @param string $method
     * @return bool
     */
    public function supportsMethod(string $method): bool
    {
        return in_array($method, $this->getSupportedMethods());
    }

    /**
     * Check if notifications are supported.
     *
     * @return bool
     */
    public function supportsNotifications(): bool
    {
        return !empty($this->getSupportedNotifications());
    }

    /**
     * Check if a specific notification type is supported.
     *
     * @param string $notificationType
     * @return bool
     */
    public function supportsNotification(string $notificationType): bool
    {
        return in_array($notificationType, $this->getSupportedNotifications());
    }

    /**
     * Check if encryption is supported.
     *
     * @return bool
     */
    public function supportedEncryption(): bool
    {
        return !empty($this->getSupportedEncryptions());
    }

    /**
     * Check if a specific encryption scheme is supported.
     *
     * @param string $encryptionScheme
     * @return bool
     */
    public function supportsEncryptionScheme(string $encryptionScheme): bool
    {
        return in_array($encryptionScheme, $this->getSupportedEncryptions());
    }
}
