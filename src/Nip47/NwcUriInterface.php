<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47;

interface NwcUriInterface
{
    /**
     * Parse a Nostr Wallet Connect URI string.
     *
     * @param string $uri The NWC URI to parse
     * @return void
     * @throws \dsbaars\nostr\Nip47\Exception\InvalidUriException
     */
    public function parse(string $uri): void;

    /**
     * Get the wallet service public key.
     *
     * @return string
     */
    public function getWalletPubkey(): string;

    /**
     * Get the relay URLs.
     *
     * @return array
     */
    public function getRelays(): array;

    /**
     * Get the client secret key.
     *
     * @return string
     */
    public function getSecret(): string;

    /**
     * Get the optional Lightning address.
     *
     * @return string|null
     */
    public function getLud16(): ?string;

    /**
     * Validate the URI format and required parameters.
     *
     * @return bool
     * @throws \dsbaars\nostr\Nip47\Exception\InvalidUriException
     */
    public function validate(): bool;

    /**
     * Generate a NWC URI string from components.
     *
     * @param string $walletPubkey
     * @param array $relays
     * @param string $secret
     * @param string|null $lud16
     * @return string
     */
    public static function generate(string $walletPubkey, array $relays, string $secret, ?string $lud16 = null): string;
}
