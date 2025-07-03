<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Event;

use swentel\nostr\Event\Event;
use dsbaars\nostr\Nip47\Response\ResponseInterface;
use swentel\nostr\Encryption\Nip04;
use swentel\nostr\Encryption\Nip44;

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
     * @param string $encryptionMethod Encryption method to use ('nip04' or 'nip44_v2')
     */
    public function __construct(ResponseInterface $response, string $walletPrivkey, string $clientPubkey, ?string $requestEventId = null, string $encryptionMethod = 'nip04')
    {
        parent::__construct();

        $this->setKind(self::KIND);
        $this->setCreatedAt(time());

        // Validate encryption method
        if (!in_array($encryptionMethod, ['nip04', 'nip44_v2'])) {
            throw new \InvalidArgumentException("Unsupported encryption method: {$encryptionMethod}. Supported methods are: nip04, nip44_v2");
        }

        // Add p tag for client
        $this->addTag(['p', $clientPubkey]);
        $this->addTag(['encryption', $encryptionMethod]);

        // Add e tag for request event if provided
        if ($requestEventId !== null) {
            $this->addTag(['e', $requestEventId]);
        }

        // Encrypt the response using the specified encryption method
        $responseJson = json_encode($response->toArray());

        if ($encryptionMethod === 'nip44_v2') {
            $conversationKey = Nip44::getConversationKey($walletPrivkey, $clientPubkey);
            $encryptedContent = Nip44::encrypt($responseJson, $conversationKey);
        } else {
            $encryptedContent = Nip04::encrypt($responseJson, $walletPrivkey, $clientPubkey);
        }

        $this->setContent($encryptedContent);
    }

    /**
     * Create response event from response object.
     *
     * @param ResponseInterface $response
     * @param string $walletPrivkey
     * @param string $clientPubkey
     * @param string|null $requestEventId
     * @param string $encryptionMethod
     * @return static
     */
    public static function fromResponse(ResponseInterface $response, string $walletPrivkey, string $clientPubkey, ?string $requestEventId = null, string $encryptionMethod = 'nip04'): static
    {
        return new static($response, $walletPrivkey, $clientPubkey, $requestEventId, $encryptionMethod);
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
