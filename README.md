# Nostr Wallet Connect (NIP-47) implementation in PHP (nostr-php)

![CI](https://github.com/dsbaars/nostr-php-nwc/actions/workflows/ci.yml/badge.svg)
![Packagist PHP Version](https://img.shields.io/packagist/v/dsbaars/nostr-php-nwc)

This project contains a complete client-side implementation of [NIP-47 Nostr Wallet Connect](https://github.com/nostr-protocol/nips/blob/master/47.md) for PHP, building upon [nostr-php](https://github.com/nostrver-se/nostr-php).

This project applied for the [NWC Hackathon Grant on Geyser](https://geyser.fund/grants/16) ([Geyser project](https://geyser.fund/project/nwcnostrphp?hero=djuri))

Async websocket communication is implemented using [valtzu/guzzle-websocket-middleware](https://packagist.org/packages/valtzu/guzzle-websocket-middleware), which is an opinionated decision and therefore the NWC functionality is provided as a separate library instead of added to the core functionality.

## Overview

Nostr Wallet Connect (NWC) allows applications to connect to Lightning wallets over the Nostr protocol in a secure, decentralized way. This implementation provides:

- **Client-side functionality** for connecting to NWC wallet services
- **Complete command support** for all major Lightning operations
- **Secure encryption** using NIP-04 (deprecated) or NIP-44 (recommended) for wallet communications
- **Comprehensive error handling** with specific exception types
- **Event-driven architecture** following Nostr patterns

## Key Features

### 🔗 URI Parsing & Validation
- Parse and validate NWC connection URIs
- Support for multiple relays and optional Lightning addresses
- Secure parameter validation and format checking

### 💰 Lightning Operations
- **Get Balance** - Check wallet balance
- **Pay Invoice** - Pay Lightning invoices  
- **Make Invoice** - Create Lightning invoices
- **Lookup Invoice** - Query invoice status
- **Get Info** - Retrieve wallet capabilities

### 🔐 Security
- NIP-04/NIP-44 encryption for wallet communications
- Proper key management and validation
- Secure event signing and verification

### ⚡ Events
- **Request Events** (kind 23194) - Encrypted commands to wallets
- **Response Events** (kind 23195) - Encrypted responses from wallets  
- **Info Events** (kind 13194) - Public wallet capability announcements

## Quick Start

### 1. Basic Connection

```php
use dsbaars\nostr\Nip47\NwcClient;
use dsbaars\nostr\Nip47\NwcUri;

// Parse NWC URI
$nwcUri = 'nostr+walletconnect://wallet_pubkey?relay=wss://relay.com&secret=client_secret';
$client = new NwcClient($nwcUri);

// Check wallet capabilities
$info = $client->getWalletInfo();
echo "Supported methods: " . implode(', ', $info->getMethods());
```

### 2. Balance Check

```php
$balance = $client->getBalance();
if ($balance->isSuccess()) {
    echo "Balance: " . $balance->getBalance() . " msats";
    echo "(" . $balance->getBalanceInSats() . " sats)";
}
```

### 3. Create Invoice

```php
$invoice = $client->makeInvoice(
    amount: 1000,        // 1000 msats = 1 sat
    description: "Test payment",
    expiry: 3600         // 1 hour
);

if ($invoice->isSuccess()) {
    echo "Invoice: " . $invoice->getInvoice();
    echo "Payment hash: " . $invoice->getPaymentHash();
}
```

### 4. Pay Invoice

```php
$payment = $client->payInvoice("lnbc1000n1...");
if ($payment->isPaymentSuccessful()) {
    echo "Payment successful!";
    echo "Preimage: " . $payment->getPreimage();
}
```

## NIP-47 Implementation Status

This implementation provides **complete coverage** of the NIP-47 specification. Below are detailed tables showing all functionality and implementation status.

### 📡 Event Kinds

| Kind | Description | Purpose | Implementation | Class |
|------|-------------|---------|---------------|-------|
| 13194 | **Info Event** | Wallet capability announcement | ✅ | `InfoEvent.php` |
| 23194 | **Request Event** | Encrypted commands to wallet | ✅ | `RequestEvent.php` |
| 23195 | **Response Event** | Encrypted responses from wallet | ✅ | `ResponseEvent.php` |
| 23196 | **Notification Event** | Real-time wallet notifications | ✅ | `NotificationEvent.php` |

### 🚀 Commands

| Command | Description | Parameters | Implementation | Class |
|---------|-------------|------------|---------------|-------|
| **`get_info`** | Get wallet capabilities | None | ✅ | `GetInfoCommand.php` |
| **`get_balance`** | Get wallet balance | None | ✅ | `GetBalanceCommand.php` |
| **`pay_invoice`** | Pay Lightning invoice | `invoice`, `amount?` | ✅ | `PayInvoiceCommand.php` |
| **`make_invoice`** | Create Lightning invoice | `amount`, `description?`, `description_hash?`, `expiry?` | ✅ | `MakeInvoiceCommand.php` |
| **`lookup_invoice`** | Lookup invoice details | `payment_hash?`, `invoice?` | ✅ | `LookupInvoiceCommand.php` |
| **`list_transactions`** | List wallet transactions | `from?`, `until?`, `limit?`, `offset?`, `unpaid?`, `type?` | ✅ | `ListTransactionsCommand.php` |
| **`pay_keysend`** | Send keysend payment | `amount`, `pubkey`, `preimage?`, `tlv_records?` | ✅ | `PayKeysendCommand.php` |
| **`multi_pay_invoice`** | Pay multiple invoices | `invoices[]` | ✅ | `MultiPayInvoiceCommand.php` |
| **`multi_pay_keysend`** | Send multiple keysends | `keysends[]` | ✅ | `MultiPayKeysendCommand.php` |

### 📨 Responses

| Response | Description | Fields | Implementation | Class |
|----------|-------------|---------|---------------|-------|
| **`get_info`** | Wallet info response | `alias`, `color`, `pubkey`, `network`, `block_height`, `block_hash`, `methods[]`, `notifications[]` | ✅ | `GetInfoResponse.php` |
| **`get_balance`** | Balance response | `balance` | ✅ | `GetBalanceResponse.php` |
| **`pay_invoice`** | Payment response | `preimage`, `fees_paid?` | ✅ | `PayInvoiceResponse.php` |
| **`make_invoice`** | Invoice creation response | `type`, `invoice?`, `description?`, `description_hash?`, `preimage?`, `payment_hash`, `amount`, `fees_paid`, `created_at`, `expires_at?`, `metadata?` | ✅ | `MakeInvoiceResponse.php` |
| **`lookup_invoice`** | Invoice lookup response | `type`, `invoice?`, `description?`, `description_hash?`, `preimage?`, `payment_hash`, `amount`, `fees_paid`, `created_at`, `expires_at?`, `settled_at?`, `metadata?` | ✅ | `LookupInvoiceResponse.php` |
| **`list_transactions`** | Transaction list response | `transactions[]` | ✅ | `ListTransactionsResponse.php` |
| **`pay_keysend`** | Keysend payment response | `preimage`, `fees_paid?` | ✅ | `PayKeysendResponse.php` |
| **`multi_pay_invoice`** | Multi-payment response | Multiple individual responses | ✅ | `MultiPayInvoiceResponse.php` |
| **`multi_pay_keysend`** | Multi-keysend response | Multiple individual responses | ✅ | `MultiPayKeysendResponse.php` |

### 🔔 Notifications

| Type | Description | Fields | Implementation | Class |
|------|-------------|---------|---------------|-------|
| **`payment_received`** | Payment successfully received | `type`, `invoice`, `description?`, `description_hash?`, `preimage`, `payment_hash`, `amount`, `fees_paid`, `created_at`, `expires_at?`, `settled_at`, `metadata?` | ✅ | `PaymentReceivedNotification.php` |
| **`payment_sent`** | Payment successfully sent | `type`, `invoice`, `description?`, `description_hash?`, `preimage`, `payment_hash`, `amount`, `fees_paid`, `created_at`, `expires_at?`, `settled_at`, `metadata?` | ✅ | `PaymentSentNotification.php` |

### ❌ Error Codes

| Code | Description | When Used | Implementation |
|------|-------------|-----------|---------------|
| **`RATE_LIMITED`** | Client sending commands too fast | Rate limiting exceeded | ✅ |
| **`NOT_IMPLEMENTED`** | Command not known/implemented | Unsupported methods | ✅ |
| **`INSUFFICIENT_BALANCE`** | Not enough funds available | Payment amount > balance | ✅ |
| **`QUOTA_EXCEEDED`** | Spending quota exceeded | Budget limits reached | ✅ |
| **`RESTRICTED`** | Operation not allowed | Permission denied | ✅ |
| **`UNAUTHORIZED`** | No wallet connected | Invalid authorization | ✅ |
| **`INTERNAL`** | Internal wallet error | Server-side issues | ✅ |
| **`OTHER`** | Other unspecified error | Catch-all error | ✅ |
| **`PAYMENT_FAILED`** | Payment processing failed | Routing/capacity issues | ✅ |
| **`NOT_FOUND`** | Invoice not found | Invalid payment hash/invoice | ✅ |

### 🔗 URI Components

| Component | Description | Required | Format | Implementation |
|-----------|-------------|----------|---------|---------------|
| **Protocol** | NWC protocol identifier | ✅ | `nostr+walletconnect://` | ✅ |
| **Pubkey** | Wallet service public key | ✅ | 32-byte hex | ✅ |
| **`relay`** | Relay URL(s) for communication | ✅ | WebSocket URL | ✅ |
| **`secret`** | Client private key | ✅ | 32-byte hex | ✅ |
| **`lud16`** | Lightning address | ❌ | Lightning address format | ✅ |

### 🔐 Security Features

| Feature | Description | Implementation | Notes |
|---------|-------------|---------------|-------|
| **NIP-04 Encryption** | End-to-end encryption of commands/responses | ✅ | Deprecated, using NIP-44 recommended |
| **NIP-44 Encryption** | End-to-end encryption of commands/responses | ✅ | All wallet communications encrypted |
| **Event Signing** | Cryptographic signatures on all events | ✅ | Prevents tampering |
| **Key Isolation** | Unique keys per wallet connection | ✅ | Improves privacy |
| **Relay Authentication** | Optional relay-level auth | ✅ | Metadata protection |
| **Request Expiration** | Time-bounded request validity | ✅ | Prevents replay attacks |

### 🎯 Advanced Features

| Feature | Description | Implementation | Class |
|---------|-------------|---------------|-------|
| **WebSocket Communication** | Real-time relay communication | ✅ | `NwcClient.php` |
| **Notification Listener** | Real-time payment notifications | ✅ | `NwcNotificationListener.php` |
| **Multi-Command Support** | Batch payment operations | ✅ | `MultiPay*Command.php` |
| **Filter Management** | Subscription filtering | ✅ | `NwcClient.php` |
| **Connection Validation** | URI and capability validation | ✅ | `NwcUri.php` |
| **Error Handling** | Comprehensive exception system | ✅ | `Exception/` namespace |
| **Logging Support** | Configurable logging | ✅ | PSR-3 compatible |

## Directory Structure

```
src/Nip47/
├── NwcClient.php                    # Main client implementation
├── NwcNotificationListener.php      # Real-time notification listener  
├── NwcUri.php                       # URI parsing and validation
├── NwcUriInterface.php              # URI interface
├── ErrorCode.php                    # NWC error codes enum
│
├── Command/                         # Command implementations
│   ├── CommandInterface.php
│   ├── AbstractCommand.php
│   ├── GetBalanceCommand.php        # ✅ get_balance
│   ├── GetInfoCommand.php           # ✅ get_info
│   ├── PayInvoiceCommand.php        # ✅ pay_invoice
│   ├── MakeInvoiceCommand.php       # ✅ make_invoice
│   ├── LookupInvoiceCommand.php     # ✅ lookup_invoice
│   ├── ListTransactionsCommand.php  # ✅ list_transactions
│   ├── PayKeysendCommand.php        # ✅ pay_keysend
│   ├── MultiPayInvoiceCommand.php   # ✅ multi_pay_invoice
│   └── MultiPayKeysendCommand.php   # ✅ multi_pay_keysend
│
├── Response/                        # Response implementations
│   ├── ResponseInterface.php
│   ├── AbstractResponse.php
│   ├── GetBalanceResponse.php       # ✅ Balance data + conversions
│   ├── GetInfoResponse.php          # ✅ Wallet info + capability checks
│   ├── PayInvoiceResponse.php       # ✅ Payment confirmation
│   ├── MakeInvoiceResponse.php      # ✅ Invoice creation
│   ├── LookupInvoiceResponse.php    # ✅ Invoice details + status
│   ├── ListTransactionsResponse.php # ✅ Transaction history
│   ├── PayKeysendResponse.php       # ✅ Keysend confirmation
│   ├── MultiPayInvoiceResponse.php  # ✅ Batch payment results
│   └── MultiPayKeysendResponse.php  # ✅ Batch keysend results
│
├── Event/                           # Nostr event implementations
│   ├── RequestEvent.php             # ✅ kind 23194
│   ├── ResponseEvent.php            # ✅ kind 23195
│   ├── InfoEvent.php                # ✅ kind 13194
│   └── NotificationEvent.php        # ✅ kind 23196
│
├── Notification/                    # Notification system
│   ├── NotificationInterface.php
│   ├── NotificationFactory.php      # ✅ Factory for creating notifications
│   ├── PaymentReceivedNotification.php # ✅ payment_received
│   └── PaymentSentNotification.php     # ✅ payment_sent
│
└── Exception/                       # Exception hierarchy
    ├── NwcException.php
    ├── InvalidUriException.php
    ├── CommandException.php
    ├── PaymentFailedException.php
    ├── InsufficientBalanceException.php
    ├── UnauthorizedException.php
    └── RateLimitedException.php
```

## Examples

This project includes working examples demonstrating all NIP-47 functionality with a centralized configuration system.

### Quick Start

First, configure your wallet connection:

```bash
cd examples/
cp config.php.example config.php
# Edit config.php with your actual NWC URI
```

### Running Examples

```bash
cd examples/

# Complete wallet functionality demo
php client-example.php

# URI parsing and validation
php uri-parsing-example.php

# End-to-end payment workflow
php payment-flow-example.php

# Real-time notification listening
php notification-listener.php

# Simple wallet info and balance check
php get-info-command.php
```

### Configuration

The examples use environment variables for easy configuration:

```bash
# Set your NWC URI
export NWC_URI="nostr+walletconnect://your-wallet-details"

# Enable verbose output
export NWC_VERBOSE=true

# Run any example
php client-example.php
```

### Example Output

```
Nostr Wallet Connect Client Example
===================================

1. Parsing NWC URI...
   ✓ Wallet Pubkey: b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4
   ✓ Relays: wss://relay.getalby.com/v1
   ✓ Secret: 71a8c14c...
   ✓ Lightning Address: wallet@example.com

2. Creating NWC client...
   ✓ Client created with pubkey: a1b2c3d4e5f6789...

3. Getting wallet info...
   ✓ Wallet connected successfully!
     - Alias: My Lightning Wallet
     - Network: bitcoin
     - Supported methods: get_balance, pay_invoice, make_invoice, lookup_invoice
     - Supported notifications: payment_received, payment_sent

4. Getting wallet balance...
   ✓ Balance: 100000 msats
     - In sats: 100 sats
     - In BTC: 0.00000100 BTC

5. Creating invoice...
   ✓ Invoice created!
     - Amount: 21000 msats
     - Description: Test invoice from NWC client
     - Invoice: lnbc210n1pjw8...

6. List transactions...
   ✓ Found 5 transactions
   - Incoming: 3
   - Outgoing: 2
   - Total amount: 150000 msats
   - Total fees: 2100 msats

Example completed successfully!
```

## Error Handling

The implementation includes comprehensive error handling:

```php
use dsbaars\nostr\Nip47\Exception\PaymentFailedException;
use dsbaars\nostr\Nip47\Exception\InsufficientBalanceException;

try {
    $payment = $client->payInvoice($invoice);
} catch (PaymentFailedException $e) {
    echo "Payment failed: " . $e->getMessage();
} catch (InsufficientBalanceException $e) {
    echo "Insufficient balance: " . $e->getMessage();
} catch (NwcException $e) {
    echo "General NWC error: " . $e->getMessage();
}
```

## NIP-47 Compliance

This implementation supports:

- ✅ All required event kinds (23194, 23195, 13194, 23196)
- ✅ All standard commands (get_info, get_balance, pay_invoice, make_invoice, lookup_invoice, list_transactions, pay_keysend, multi_pay_invoice, multi_pay_keysend)
- ✅ All notification types (payment_received, payment_sent)
- ✅ All error codes per specification
- ✅ Proper NIP-04/NIP-44 encryption
- ✅ URI format validation
- ✅ Relay communication
- ✅ Event signing and verification
- ✅ Real-time WebSocket notifications

## Security Considerations

- **Encryption**: All communications are encrypted using NIP-04/NIP-44
- **Authentication**: Events are signed with client private keys
- **Validation**: All inputs are validated before processing
- **Error Handling**: Sensitive information is not leaked in errors
- **Timeouts**: Commands have reasonable timeout limits
- **Rate Limiting**: Built-in support for rate limit handling

## Testing

⚠️ **Important**: Be careful when testing with real wallets and real Bitcoin!

### Running Tests

```bash
# Run all tests
php vendor/bin/phpunit tests/

# Run specific test file
php vendor/bin/phpunit tests/Nip47Test.php
```

### Testing with Examples

1. **Use testnet wallets** for development
2. **Start with small amounts** - examples use 1-100 sats by default
3. **Configure safely** - set `NWC_VERBOSE=true` for detailed output
4. **Verify parameters** before executing payments
5. **Check capabilities** with `get-info-command.php` first

### Test Configuration

```bash
# Use testnet amounts
export NWC_TEST_AMOUNT=1000      # 1 sat
export NWC_SMALL_AMOUNT=21000    # 21 sats  
export NWC_MEDIUM_AMOUNT=100000  # 100 sats

# Enable verbose mode
export NWC_VERBOSE=true

# Test basic functionality
cd examples/
php get-info-command.php
php client-example.php
```

## Integration

To integrate NWC into your application:

1. **Parse the NWC URI** from user input or configuration
2. **Create an NwcClient** instance  
3. **Check wallet capabilities** with `getWalletInfo()`
4. **Implement your payment flow** using the available commands
5. **Handle errors gracefully** with proper exception handling