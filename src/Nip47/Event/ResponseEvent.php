<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;
use dsbaars\nostr\Nip47\Response\ResponseInterface;
use swentel\nostr\Encryption\Nip04;

/**
 * NWC Response Event (kind 23195).
 *
 * Encrypted responses sent from wallet services to clients.
 */
class ResponseEvent extends Event
{
    public const KIND = 23195;

    /**
     * Create a new response event.
     *
     * @param ResponseInterface $response The response to send
     * @param string $walletPrivkey Wallet service private key
     * @param string $clientPubkey Client's public key
     * @param string|null $requestEventId Optional request event ID to reference
     */
    public function __construct(ResponseInterface $response, string $walletPrivkey, string $clientPubkey, ?string $requestEventId = null)
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Add p tag for client
        $this->addTag(['p', $clientPubkey]);

        // Add e tag for request event if provided
        if ($requestEventId !== null) {
            $this->addTag(['e', $requestEventId]);
        }

        // Encrypt the response using NIP-04
        $responseJson = json_encode($response->toArray());
        $encryptedContent = Nip04::encrypt($responseJson, $walletPrivkey, $clientPubkey);
        $this->setContent($encryptedContent);
    }

    /**
     * Create response event from response object.
     *
     * @param ResponseInterface $response
     * @param string $walletPrivkey
     * @param string $clientPubkey
     * @param string|null $requestEventId
     * @return static
     */
    public static function fromResponse(ResponseInterface $response, string $walletPrivkey, string $clientPubkey, ?string $requestEventId = null): static
    {
        return new static($response, $walletPrivkey, $clientPubkey, $requestEventId);
    }

    /**
     * Decrypt and parse the response from this event.
     *
     * @param string $privateKey The private key to decrypt with
     * @param string $publicKey The public key to decrypt with
     * @return array The decrypted response data
     * @throws \Exception
     */
    public function decryptResponse(string $privateKey, string $publicKey): array
    {
        $decrypted = Nip04::decrypt($this->getContent(), $privateKey, $publicKey);
        return json_decode($decrypted, true);
    }
}
