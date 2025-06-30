<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/config.php';

use dsbaars\nostr\Nip47\NwcUri;
use dsbaars\nostr\Nip47\Exception\InvalidUriException;

echo "NWC URI Parsing and Validation Example" . PHP_EOL;
echo "======================================" . PHP_EOL . PHP_EOL;

// Example 1: Valid NWC URI
echo "1. Parsing valid NWC URI:" . PHP_EOL;
$validUri = 'nostr+walletconnect://b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4?relay=wss%3A%2F%2Frelay.damus.io&secret=71a8c14c1407c113601079c4302dab36460f0ccd0ad506f1f2dc73b5100e4f3c&lud16=wallet%40example.com';

try {
    $nwcUri = new NwcUri($validUri);
    echo "   ✓ URI parsed successfully!" . PHP_EOL;
    echo "   - Wallet Pubkey: " . $nwcUri->getWalletPubkey() . PHP_EOL;
    echo "   - Relays: " . implode(', ', $nwcUri->getRelays()) . PHP_EOL;
    echo "   - Secret: " . $nwcUri->getSecret() . PHP_EOL;
    echo "   - Lightning Address: " . ($nwcUri->getLud16() ?? 'None') . PHP_EOL;

    // Convert back to string
    echo "   - Reconstructed URI: " . $nwcUri->__toString() . PHP_EOL;
} catch (InvalidUriException $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// Example 2: Generate NWC URI
echo "2. Generating NWC URI:" . PHP_EOL;
try {
    $walletPubkey = 'a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890';
    $relays = ['wss://relay.example.com', 'wss://backup.relay.com'];
    $secret = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    $lud16 = 'user@lightning.address';

    $generatedUri = NwcUri::generate($walletPubkey, $relays, $secret, $lud16);
    echo "   ✓ Generated URI: " . $generatedUri . PHP_EOL;

    // Parse the generated URI to verify
    $parsedGenerated = new NwcUri($generatedUri);
    echo "   ✓ Verification: URI can be parsed back correctly" . PHP_EOL;
} catch (InvalidUriException $e) {
    echo "   ✗ Error generating URI: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// Example 3: Invalid URI examples
echo "3. Testing invalid URIs:" . PHP_EOL;

$invalidUris = [
    'invalid://not-a-nwc-uri' => 'Wrong protocol',
    'nostr+walletconnect://invalid-pubkey?relay=wss://relay.com&secret=abc' => 'Invalid pubkey format',
    'nostr+walletconnect://b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4?secret=abc' => 'Missing relay parameter',
    'nostr+walletconnect://b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4?relay=wss://relay.com' => 'Missing secret parameter',
    'nostr+walletconnect://b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4?relay=http://insecure.relay&secret=71a8c14c1407c113601079c4302dab36460f0ccd0ad506f1f2dc73b5100e4f3c' => 'Non-WSS relay',
    'nostr+walletconnect://b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4?relay=wss://relay.com&secret=tooshort' => 'Invalid secret format',
];

foreach ($invalidUris as $uri => $expectedError) {
    try {
        new NwcUri($uri);
        echo "   ✗ Should have failed: $expectedError" . PHP_EOL;
    } catch (InvalidUriException $e) {
        echo "   ✓ Correctly rejected: $expectedError - " . $e->getMessage() . PHP_EOL;
    }
}
echo PHP_EOL;

// Example 4: Array representation
echo "4. Array representation:" . PHP_EOL;
try {
    $nwcUri = new NwcUri($validUri);
    $array = $nwcUri->toArray();
    echo "   ✓ Array format:" . PHP_EOL;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            echo "     - $key: [" . implode(', ', $value) . "]" . PHP_EOL;
        } else {
            echo "     - $key: " . ($value ?? 'null') . PHP_EOL;
        }
    }
} catch (InvalidUriException $e) {
    echo "   ✗ Error: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

echo "URI parsing examples completed!" . PHP_EOL;
