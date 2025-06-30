<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/config.php';

use dsbaars\nostr\Nip47\NwcClient;
use dsbaars\nostr\Nip47\NwcUri;
use dsbaars\nostr\Nip47\Exception\NwcException;

try {
    // Example NWC URI - replace with your actual URI
    $nwcUriString = $config['nwc_uri'];

    echo "Nostr Wallet Connect Client Example" . PHP_EOL;
    echo "===================================" . PHP_EOL . PHP_EOL;

    // Parse and validate the NWC URI
    echo "1. Parsing NWC URI..." . PHP_EOL;
    $nwcUri = new NwcUri($nwcUriString);
    echo "   ✓ Wallet Pubkey: " . $nwcUri->getWalletPubkey() . PHP_EOL;
    echo "   ✓ Relays: " . implode(', ', $nwcUri->getRelays()) . PHP_EOL;
    echo "   ✓ Secret: " . substr($nwcUri->getSecret(), 0, 8) . "..." . PHP_EOL;
    if ($nwcUri->getLud16()) {
        echo "   ✓ Lightning Address: " . $nwcUri->getLud16() . PHP_EOL;
    }
    echo PHP_EOL;

    // Create NWC client
    echo "2. Creating NWC client..." . PHP_EOL;
    $client = new NwcClient($nwcUri);
    echo "   ✓ Client created with pubkey: " . $client->getClientPubkey() . PHP_EOL;
    echo PHP_EOL;

    // Get wallet balance
    echo "3. Getting wallet balance..." . PHP_EOL;
    try {
        $info = $client->getWalletInfo();
        if ($info->isSuccess()) {
            echo "   ✓ Wallet connected successfully!" . PHP_EOL;
            if ($info->getAlias()) {
                echo "     - Alias: " . $info->getAlias() . PHP_EOL;
            }
            if ($info->getNetwork()) {
                echo "     - Network: " . $info->getNetwork() . PHP_EOL;
            }
            echo "     - Supported methods: " . implode(', ', $info->getMethods()) . PHP_EOL;
            if ($info->supportsNotifications()) {
                echo "     - Supported notifications: " . implode(', ', $info->getNotifications()) . PHP_EOL;
            }
        } else {
            echo "   ✗ Failed to get wallet info: " . $info->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error getting wallet info: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    echo "4. Getting wallet balance..." . PHP_EOL;
    try {
        $balance = $client->getBalance();
        if ($balance->isSuccess()) {
            echo "   ✓ Balance: " . $balance->getBalance() . " msats" . PHP_EOL;
            echo "     - In sats: " . $balance->getBalanceInSats() . " sats" . PHP_EOL;
            echo "     - In BTC: " . number_format($balance->getBalanceInBtc(), 8) . " BTC" . PHP_EOL;
        } else {
            echo "   ✗ Failed to get balance: " . $balance->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error getting balance: " . $e->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
