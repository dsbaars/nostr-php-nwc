<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

/**
 * Get Info response implementation.
 */
class GetInfoResponse extends AbstractResponse
{
    /**
     * Get the wallet alias/name.
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->getResultField('alias');
    }

    /**
     * Get the wallet color (hex string).
     *
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->getResultField('color');
    }

    /**
     * Get the wallet public key.
     *
     * @return string|null
     */
    public function getPubkey(): ?string
    {
        return $this->getResultField('pubkey');
    }

    /**
     * Get the network (mainnet, testnet, signet, regtest).
     *
     * @return string|null
     */
    public function getNetwork(): ?string
    {
        return $this->getResultField('network');
    }

    /**
     * Get the current block height.
     *
     * @return int|null
     */
    public function getBlockHeight(): ?int
    {
        return $this->getResultField('block_height');
    }

    /**
     * Get the current block hash.
     *
     * @return string|null
     */
    public function getBlockHash(): ?string
    {
        return $this->getResultField('block_hash');
    }

    /**
     * Get the list of supported methods.
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->getResultField('methods', []);
    }

    /**
     * Get the list of supported notifications.
     *
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->getResultField('notifications', []);
    }

    /**
     * Check if a specific method is supported.
     *
     * @param string $method
     * @return bool
     */
    public function supportsMethod(string $method): bool
    {
        return in_array($method, $this->getMethods());
    }

    /**
     * Check if notifications are supported.
     *
     * @return bool
     */
    public function supportsNotifications(): bool
    {
        return !empty($this->getNotifications());
    }

    /**
     * Check if a specific notification type is supported.
     *
     * @param string $notificationType
     * @return bool
     */
    public function supportsNotification(string $notificationType): bool
    {
        return in_array($notificationType, $this->getNotifications());
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
     * Get the list of supported encryption schemes.
     *
     * @return array
     */
    public function getSupportedEncryptions(): array
    {
        return $this->getResultField('encryption', []);
    }
}
