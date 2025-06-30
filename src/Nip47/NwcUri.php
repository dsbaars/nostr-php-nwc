<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47;

use dsbaars\nostr\Nip47\Exception\InvalidUriException;

/**
 * Nostr Wallet Connect URI parser and validator.
 *
 * Handles URI format: nostr+walletconnect://{walletPubkey}?relay={relayUrl}&secret={secret}&lud16={lud16}
 */
class NwcUri implements NwcUriInterface
{
    private const PROTOCOL = 'nostr+walletconnect://';

    private string $walletPubkey = '';
    private array $relays = [];
    private string $secret = '';
    private ?string $lud16 = null;

    /**
     * Create NwcUri instance from URI string.
     *
     * @param string|null $uri Optional URI to parse immediately
     * @throws InvalidUriException
     */
    public function __construct(?string $uri = null)
    {
        if ($uri !== null) {
            $this->parse($uri);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $uri): void
    {
        if (!str_starts_with($uri, self::PROTOCOL)) {
            throw new InvalidUriException("URI must start with '" . self::PROTOCOL . "'");
        }

        // Remove protocol
        $uriWithoutProtocol = substr($uri, strlen(self::PROTOCOL));

        // Split pubkey and query string
        $parts = explode('?', $uriWithoutProtocol, 2);

        if (count($parts) !== 2) {
            throw new InvalidUriException("URI must contain query parameters");
        }

        $this->walletPubkey = $parts[0];

        // Validate pubkey format (64 character hex string)
        if (!preg_match('/^[a-f0-9]{64}$/i', $this->walletPubkey)) {
            throw new InvalidUriException("Invalid wallet public key format");
        }

        // Parse query parameters manually to handle multiple relays
        $queryString = $parts[1];
        $queryPairs = explode('&', $queryString);
        $params = [];

        foreach ($queryPairs as $pair) {
            if (empty($pair)) {
                continue;
            }

            $keyValue = explode('=', $pair, 2);
            if (count($keyValue) !== 2) {
                continue;
            }

            $key = urldecode($keyValue[0]);
            $value = urldecode($keyValue[1]);

            if ($key === 'relay') {
                $params['relay'][] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        // Validate required parameters
        if (!isset($params['relay'])) {
            throw new InvalidUriException("Missing required 'relay' parameter");
        }

        if (!isset($params['secret'])) {
            throw new InvalidUriException("Missing required 'secret' parameter");
        }

        // Handle multiple relays
        $relays = $params['relay'];
        foreach ($relays as $relay) {
            if (!filter_var($relay, FILTER_VALIDATE_URL) || !str_starts_with($relay, 'wss://')) {
                throw new InvalidUriException("Invalid relay URL: $relay");
            }
        }
        $this->relays = $relays;

        // Validate secret format (64 character hex string)
        if (!preg_match('/^[a-f0-9]{64}$/i', $params['secret'])) {
            throw new InvalidUriException("Invalid secret format");
        }
        $this->secret = $params['secret'];

        // Optional lud16 parameter
        if (isset($params['lud16'])) {
            if (!filter_var($params['lud16'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidUriException("Invalid lud16 format");
            }
            $this->lud16 = $params['lud16'];
        }

        $this->validate();
    }

    /**
     * {@inheritdoc}
     */
    public function getWalletPubkey(): string
    {
        return $this->walletPubkey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelays(): array
    {
        return $this->relays;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * {@inheritdoc}
     */
    public function getLud16(): ?string
    {
        return $this->lud16;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        if (empty($this->walletPubkey)) {
            throw new InvalidUriException("Wallet public key is required");
        }

        if (empty($this->relays)) {
            throw new InvalidUriException("At least one relay is required");
        }

        if (empty($this->secret)) {
            throw new InvalidUriException("Secret is required");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function generate(string $walletPubkey, array $relays, string $secret, ?string $lud16 = null): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $walletPubkey)) {
            throw new InvalidUriException("Invalid wallet public key format");
        }

        if (empty($relays)) {
            throw new InvalidUriException("At least one relay is required");
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', $secret)) {
            throw new InvalidUriException("Invalid secret format");
        }

        $queryParts = [];

        // Add relays
        foreach ($relays as $relay) {
            if (!filter_var($relay, FILTER_VALIDATE_URL) || !str_starts_with($relay, 'wss://')) {
                throw new InvalidUriException("Invalid relay URL: $relay");
            }
            $queryParts[] = 'relay=' . urlencode($relay);
        }

        // Add secret
        $queryParts[] = 'secret=' . urlencode($secret);

        // Add optional lud16
        if ($lud16 !== null) {
            if (!filter_var($lud16, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidUriException("Invalid lud16 format");
            }
            $queryParts[] = 'lud16=' . urlencode($lud16);
        }

        return self::PROTOCOL . $walletPubkey . '?' . implode('&', $queryParts);
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'wallet_pubkey' => $this->walletPubkey,
            'relays' => $this->relays,
            'secret' => $this->secret,
            'lud16' => $this->lud16,
        ];
    }

    /**
     * Convert to string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return self::generate($this->walletPubkey, $this->relays, $this->secret, $this->lud16);
    }
}
