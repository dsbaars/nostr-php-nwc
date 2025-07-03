<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;
use dsbaars\nostr\Nip47\Command\CommandInterface;
use swentel\nostr\Encryption\Nip04;
use swentel\nostr\Encryption\Nip44;

/**
 * NWC Request Event (kind 23194).
 *
 * Encrypted commands sent to wallet services.
 */
class RequestEvent extends Event
{
    public const KIND = 23194;

    /**
     * Create a new request event.
     *
     * @param CommandInterface $command The command to send
     * @param string $clientPrivkey Client's private key (from NWC URI secret)
     * @param string $walletPubkey Wallet service public key
     * @param int|null $expiration Optional expiration timestamp
     * @param string $encryptionMethod Encryption method to use ('nip04' or 'nip44_v2')
     */
    public function __construct(CommandInterface $command, string $clientPrivkey, string $walletPubkey, ?int $expiration = null, string $encryptionMethod = 'nip04')
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Validate encryption method
        if (!in_array($encryptionMethod, ['nip04', 'nip44_v2'])) {
            throw new \InvalidArgumentException("Unsupported encryption method: {$encryptionMethod}. Supported methods are: nip04, nip44_v2");
        }

        // Add p tag for wallet service
        $this->addTag(['p', $walletPubkey]);
        $this->addTag(['encryption', $encryptionMethod]);

        // Add expiration tag if provided
        if ($expiration !== null) {
            $this->addTag(['expiration', (string) $expiration]);
        }

        // Encrypt the command using the specified encryption method
        $commandJson = json_encode($command->toArray());

        if ($encryptionMethod === 'nip44_v2') {
            $conversationKey = Nip44::getConversationKey($clientPrivkey, $walletPubkey);
            $encryptedContent = Nip44::encrypt($commandJson, $conversationKey);
        } else {
            $encryptedContent = Nip04::encrypt($commandJson, $clientPrivkey, $walletPubkey);
        }

        $this->setContent($encryptedContent);
    }

    /**
     * Create request event from command.
     *
     * @param CommandInterface $command
     * @param string $clientPrivkey
     * @param string $walletPubkey
     * @param int|null $expiration
     * @param string $encryptionMethod
     * @return static
     */
    public static function fromCommand(CommandInterface $command, string $clientPrivkey, string $walletPubkey, ?int $expiration = null, string $encryptionMethod = 'nip04'): static
    {
        return new static($command, $clientPrivkey, $walletPubkey, $expiration, $encryptionMethod);
    }

    /**
     * Decrypt and parse the command from this event.
     *
     * @param string $privateKey The private key to decrypt with
     * @param string $publicKey The public key to decrypt with
     * @return array The decrypted command data
     * @throws \Exception
     */
    public function decryptCommand(string $privateKey, string $publicKey): array
    {
        // Determine encryption method from tags
        $encryptionMethod = $this->getEncryptionMethod();

        if ($encryptionMethod === 'nip44_v2') {
            $conversationKey = Nip44::getConversationKey($privateKey, $publicKey);
            $decrypted = Nip44::decrypt($this->getContent(), $conversationKey);
        } else {
            // Default to nip04 for backward compatibility
            $decrypted = Nip04::decrypt($this->getContent(), $privateKey, $publicKey);
        }

        return json_decode($decrypted, true);
    }

    /**
     * Get the encryption method used for this event.
     *
     * @return string The encryption method ('nip04' or 'nip44_v2')
     */
    public function getEncryptionMethod(): string
    {
        $tags = $this->getTags();
        foreach ($tags as $tag) {
            if (isset($tag[0]) && $tag[0] === 'encryption' && isset($tag[1])) {
                return $tag[1];
            }
        }

        // Default to nip04 for backward compatibility
        return 'nip04';
    }
}
