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

    // Get wallet info and capabilities
    echo "3. Getting wallet info..." . PHP_EOL;
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
            if ($info->supportedEncryption()) {
                echo "     - Supported encryption: " . implode(', ', $info->getSupportedEncryptions()) . PHP_EOL;
            }

            if (in_array('nip44_v2', $info->getSupportedEncryptions())) {
                $client->setEncryption('nip44_v2');
                echo "     - Using encryption: " . $client->getEncryptionMethod() . PHP_EOL;
            } 
        } else {
            echo "   ✗ Failed to get wallet info: " . $info->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error getting wallet info: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    // Get wallet balance
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

    // Create an invoice
    echo "5. Creating invoice..." . PHP_EOL;
    try {
        $amount = $config['small_amount']; // 1000 msats = 1 sat
        $description = "Test invoice from NWC client";

        $invoice = $client->makeInvoice($amount, $description);
        if ($invoice->isSuccess()) {
            echo "   ✓ Invoice created!" . PHP_EOL;
            echo "     - Amount: " . $invoice->getAmount() . " msats" . PHP_EOL;
            echo "     - Description: " . $invoice->getDescription() . PHP_EOL;
            // echo "     - Payment hash: " . substr($invoice->getPaymentHash(), 0, 16) . "..." . PHP_EOL;
            echo "     - Invoice: " . $invoice->getInvoice() . PHP_EOL;
        } else {
            echo "   ✗ Failed to create invoice: " . $invoice->getErrorMessage() . PHP_EOL;
        }
    } catch (NwcException $e) {
        echo "   ✗ Error creating invoice: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;


    echo "6. List transactions..." . PHP_EOL;
    try {
        $transactions = $client->listTransactions(
            from: time() - 86400 * 7, // Last 7 days
            limit: 10, // Max 10 transactions
        );

        if ($transactions->hasTransactions()) {
            echo "   ✓ Found {$transactions->getTransactionCount()} transactions\n";
            echo "   - Incoming: " . count($transactions->getIncomingTransactions()) . "\n";
            echo "   - Outgoing: " . count($transactions->getOutgoingTransactions()) . "\n";
            echo "   - Total amount: {$transactions->getTotalAmount()} msats\n";
            echo "   - Total fees: {$transactions->getTotalFees()} msats\n";
        } else {
            echo "   ℹ No transactions found\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ List transactions failed: " . $e->getMessage() . "\n";
    }
    echo PHP_EOL;

    // Pay an invoice (commented out to avoid actual payments)
    // echo "6. Paying invoice..." . PHP_EOL;
    // try {
    //     $invoiceToPay = "lnbc210n1p5xgr4gpp57ht97c9pndqnq0xaus5ft4e6e9wftc03xxqs6r7vdv4cyy65d5jsdpv2phhwetjv4jzqcneypqyc6t8dp6xu6twva2xjuzzda6qcqzysxqrrsssp5vmt84msaxkxu4vazjej95upw2x3saunc0krf9nnem4sz86kjr97q9qxpqysgqs6t9sqxynnfsz55khv5hnyf3gqsdznk09ek38y8ftvwwrvp5mgvpky3u5dv4sy98q47nut307jnvd67lz094fpfrqku6heq00k6h5vqpp3ql9t"; // Replace with actual invoice

    //     $payment = $client->payInvoice($invoiceToPay);
    //     if ($payment->isPaymentSuccessful()) {
    //         echo "   ✓ Payment successful!" . PHP_EOL;
    //         echo "     - Preimage: " . $payment->getPreimage() . PHP_EOL;
    //         if ($payment->getFeesPaid()) {
    //             echo "     - Fees paid: " . $payment->getFeesPaid() . " msats" . PHP_EOL;
    //         }
    //     } else {
    //         echo "   ✗ Payment failed: " . $payment->getErrorMessage() . PHP_EOL;
    //     }
    // } catch (NwcException $e) {
    //     echo "   ✗ Error paying invoice: " . $e->getMessage() . PHP_EOL;
    // }
    // echo PHP_EOL;

    echo "Example completed successfully!" . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    if ($e instanceof NwcException) {
        echo "This is an NWC-specific error." . PHP_EOL;
    }
}
