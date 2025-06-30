<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/config.php';

use dsbaars\nostr\Nip47\NwcNotificationListener;
use dsbaars\nostr\Nip47\Notification\PaymentReceivedNotification;
use dsbaars\nostr\Nip47\Notification\PaymentSentNotification;
use dsbaars\nostr\Nip47\NwcClient;
use dsbaars\nostr\Nip47\NwcUri;

try {
    // Example NWC URI - replace with your actual URI
    $nwcUriString = $config['nwc_uri']; // readonly

    echo "Real-Time NWC Notification Listener" . PHP_EOL;
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

    // Create notification listener with logger and verbose output
    echo "3. Setting up notification listener..." . PHP_EOL;
    $listener = new NwcNotificationListener(
        client: $client,
        verbose: $config['verbose'],           // Enable verbose console output
        lookbackSeconds: $config['lookback_seconds'],      // Look back 1 minute for recent notifications
    );

    // Set up payment received callback
    $listener->onPaymentReceived(function (PaymentReceivedNotification $notification, \stdClass $event) {
        echo "🎉 CUSTOM CALLBACK: Payment received!" . PHP_EOL;
        echo "    💰 Received " . $notification->getAmountInSats() . " sats" . PHP_EOL;
        if ($notification->getDescription()) {
            echo "    📝 " . $notification->getDescription() . PHP_EOL;
        }
        echo "    🆔 Event ID: " . $event->id . PHP_EOL;
        echo PHP_EOL;
    });

    // Set up payment sent callback
    $listener->onPaymentSent(function (PaymentSentNotification $notification, \stdClass $event) {
        echo "💸 CUSTOM CALLBACK: Payment sent!" . PHP_EOL;
        echo "    💰 Sent " . $notification->getAmountInSats() . " sats" . PHP_EOL;
        echo "    💳 Fees " . $notification->getFeesPaidInSats() . " sats" . PHP_EOL;
        if ($notification->getDescription()) {
            echo "    📝 " . $notification->getDescription() . PHP_EOL;
        }
        echo "    🆔 Event ID: " . $event->id . PHP_EOL;
        echo PHP_EOL;
    });

    echo "   ✓ Callbacks configured" . PHP_EOL;
    echo PHP_EOL;

    // Start listening
    echo "4. Starting notification listener..." . PHP_EOL;
    $listener->listen();

} catch (Exception $e) {
    echo 'Exception error: ' . $e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . $e->getTraceAsString() . PHP_EOL;
}
