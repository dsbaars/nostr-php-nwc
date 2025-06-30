<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/config.php';

use dsbaars\nostr\Nip47\NwcClient;
use dsbaars\nostr\Nip47\NwcUri;
use dsbaars\nostr\Nip47\Command\PayInvoiceCommand;
use dsbaars\nostr\Nip47\Command\MakeInvoiceCommand;
use dsbaars\nostr\Nip47\Command\LookupInvoiceCommand;
use dsbaars\nostr\Nip47\Command\GetBalanceCommand;
use dsbaars\nostr\Nip47\Exception\NwcException;

echo "NWC Payment Flow Example" . PHP_EOL;
echo "========================" . PHP_EOL . PHP_EOL;

try {
    // Example NWC URI - replace with your actual URI
    $nwcUriString = $config['nwc_uri'];
    $nwcUrlString = 'nostr+walletconnect://1ff6232670f56e6a3d9f602433131b8aefb2261f774a54750cabc7d1df95390e?relay=wss://relay.getalby.com/v1&secret=f8ffe5480d7629317d77f155cb3c7376c149ba50804df516dd0ab0a421d4fbdc&lud16=zestywave833927@getalby.com'; // readonly
    // Create NWC client
    echo "1. Connecting to wallet..." . PHP_EOL;
    $client = new NwcClient($nwcUriString);
    echo "   ✓ Connected to wallet: " . substr($client->getWalletPubkey(), 0, 16) . "..." . PHP_EOL;
    echo PHP_EOL;

    // Check wallet capabilities
    echo "2. Checking wallet capabilities..." . PHP_EOL;
    try {
        $info = $client->getWalletInfo();
        if ($info->isSuccess()) {
            $methods = $info->getMethods();
            echo "   ✓ Supported methods: " . implode(', ', $methods) . PHP_EOL;

            // Check required methods
            $requiredMethods = ['get_balance', 'make_invoice', 'pay_invoice', 'lookup_invoice'];
            $missingMethods = array_diff($requiredMethods, $methods);

            if (!empty($missingMethods)) {
                echo "   ⚠ Warning: Missing methods: " . implode(', ', $missingMethods) . PHP_EOL;
            } else {
                echo "   ✓ All required methods are supported" . PHP_EOL;
            }
        } else {
            echo "   ⚠ Could not get wallet info: " . $info->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ⚠ Could not check capabilities: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    // Check initial balance
    echo "3. Checking initial balance..." . PHP_EOL;
    try {
        $balance = $client->getBalance();
        if ($balance->isSuccess()) {
            echo "   ✓ Current balance: " . number_format($balance->getBalance()) . " msats" . PHP_EOL;
            echo "     (" . number_format($balance->getBalanceInSats()) . " sats)" . PHP_EOL;
        } else {
            echo "   ✗ Failed to get balance: " . $balance->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error getting balance: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    // Create an invoice
    echo "4. Creating a test invoice..." . PHP_EOL;
    try {
        $invoiceAmount = 1000; // 1000 msats = 1 sat
        $description = "Test invoice for NWC payment flow";
        $expiry = 3600; // 1 hour

        $invoiceResponse = $client->makeInvoice($invoiceAmount, $description, null, $expiry);
        if ($invoiceResponse->isSuccess()) {
            echo "   ✓ Invoice created successfully!" . PHP_EOL;
            echo "     - Amount: " . $invoiceResponse->getAmount() . " msats" . PHP_EOL;
            echo "     - Description: " . $invoiceResponse->getDescription() . PHP_EOL;
            echo "     - Payment Hash: " . substr($invoiceResponse->getPaymentHash(), 0, 16) . "..." . PHP_EOL;
            echo "     - Expires at: " . date('Y-m-d H:i:s', $invoiceResponse->getExpiresAt()) . PHP_EOL;
            echo "     - Invoice: " . substr($invoiceResponse->getInvoice(), 0, 50) . "..." . PHP_EOL;

            $createdInvoice = $invoiceResponse->getInvoice();
            $paymentHash = $invoiceResponse->getPaymentHash();
        } else {
            echo "   ✗ Failed to create invoice: " . $invoiceResponse->getErrorMessage() . PHP_EOL;
            $createdInvoice = null;
            $paymentHash = null;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error creating invoice: " . $e->getMessage() . PHP_EOL;
        $createdInvoice = null;
        $paymentHash = null;
    }
    echo PHP_EOL;

    // Lookup the created invoice
    if ($paymentHash) {
        echo "5. Looking up the created invoice..." . PHP_EOL;
        try {
            $lookupResponse = $client->executeCommand(
                new LookupInvoiceCommand($paymentHash),
                \dsbaars\nostr\Nip47\Response\LookupInvoiceResponse::class,
            );

            if ($lookupResponse->isSuccess()) {
                echo "   ✓ Invoice lookup successful!" . PHP_EOL;
                echo "     - Type: " . $lookupResponse->getType() . PHP_EOL;
                echo "     - Amount: " . $lookupResponse->getAmount() . " msats" . PHP_EOL;
                echo "     - Settled: " . ($lookupResponse->isSettled() ? 'Yes' : 'No') . PHP_EOL;
                echo "     - Expired: " . ($lookupResponse->isExpired() ? 'Yes' : 'No') . PHP_EOL;
            } else {
                echo "   ✗ Failed to lookup invoice: " . $lookupResponse->getErrorMessage() . PHP_EOL;
            }
        } catch (NwcException $e) {
            echo "   ✗ Error looking up invoice: " . $e->getMessage() . PHP_EOL;
        }
        echo PHP_EOL;
    }

    // Demonstrate payment (commented out for safety)
    echo "6. Payment demonstration (disabled for safety):" . PHP_EOL;
    echo "   To test payments, uncomment the payment section below and provide a real invoice." . PHP_EOL;
    echo "   ⚠ WARNING: This will make actual Lightning payments!" . PHP_EOL;
    echo PHP_EOL;

    /*
    // UNCOMMENT THIS SECTION TO TEST ACTUAL PAYMENTS
    // WARNING: This will make real Lightning payments!

    $testInvoice = "lnbc10n1..."; // Replace with actual test invoice

    echo "6. Paying test invoice..." . PHP_EOL;
    try {
        $paymentResponse = $client->payInvoice($testInvoice);
        if ($paymentResponse->isPaymentSuccessful()) {
            echo "   ✓ Payment successful!" . PHP_EOL;
            echo "     - Preimage: " . $paymentResponse->getPreimage() . PHP_EOL;
            if ($paymentResponse->getFeesPaid()) {
                echo "     - Fees paid: " . $paymentResponse->getFeesPaid() . " msats" . PHP_EOL;
            }
        } else {
            echo "   ✗ Payment failed: " . $paymentResponse->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error making payment: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
    */

    // Final balance check
    echo "7. Final balance check..." . PHP_EOL;
    try {
        $finalBalance = $client->getBalance();
        if ($finalBalance->isSuccess()) {
            echo "   ✓ Final balance: " . number_format($finalBalance->getBalance()) . " msats" . PHP_EOL;
            echo "     (" . number_format($finalBalance->getBalanceInSats()) . " sats)" . PHP_EOL;
        } else {
            echo "   ✗ Failed to get final balance: " . $finalBalance->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error getting final balance: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    echo "Payment flow example completed!" . PHP_EOL;
    echo PHP_EOL;
    echo "Summary:" . PHP_EOL;
    echo "- Connected to NWC wallet service" . PHP_EOL;
    echo "- Checked wallet capabilities and balance" . PHP_EOL;
    echo "- Created and looked up an invoice" . PHP_EOL;
    echo "- Demonstrated payment flow structure" . PHP_EOL;
    echo PHP_EOL;
    echo "To test actual payments:" . PHP_EOL;
    echo "1. Uncomment the payment section" . PHP_EOL;
    echo "2. Replace the test invoice with a real one" . PHP_EOL;
    echo "3. Make sure you have sufficient balance" . PHP_EOL;
    echo "4. Run with caution - real Lightning payments will be made!" . PHP_EOL;

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    if ($e instanceof NwcException) {
        echo "This is an NWC-specific error." . PHP_EOL;
    }
}
