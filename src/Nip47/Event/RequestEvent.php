<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;
use dsbaars\nostr\Nip47\Command\CommandInterface;
use swentel\nostr\Encryption\Nip04;

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
     */
    public function __construct(CommandInterface $command, string $clientPrivkey, string $walletPubkey, ?int $expiration = null)
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Add p tag for wallet service
        $this->addTag(['p', $walletPubkey]);
        $this->addTag(['encryption', 'nip04']);

        // Add expiration tag if provided
        if ($expiration !== null) {
            $this->addTag(['expiration', (string) $expiration]);
        }

        // Encrypt the command using NIP-04
        $commandJson = json_encode($command->toArray());

        $encryptedContent = Nip04::encrypt($commandJson, $clientPrivkey, $walletPubkey);

        $this->setContent($encryptedContent);
    }

    /**
     * Create request event from command.
     *
     * @param CommandInterface $command
     * @param string $clientPrivkey
     * @param string $walletPubkey
     * @param int|null $expiration
     * @return static
     */
    public static function fromCommand(CommandInterface $command, string $clientPrivkey, string $walletPubkey, ?int $expiration = null): static
    {
        return new static($command, $clientPrivkey, $walletPubkey, $expiration);
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
        $decrypted = Nip04::decrypt($this->getContent(), $privateKey, $publicKey);
        return json_decode($decrypted, true);
    }
}
