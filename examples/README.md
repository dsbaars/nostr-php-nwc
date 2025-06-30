# NWC Examples

This directory contains working examples demonstrating all NIP-47 Nostr Wallet Connect functionality.

## Quick Start

### 1. Configure Your Wallet

Copy the example config file and update it with your NWC URI:

```bash
cp config.php.example config.php
```

Edit `config.php` and replace the example values with your actual wallet connection details.

### 2. Run Examples

```bash
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

## Configuration

The examples use a centralized configuration system:

- **`config.php.example`** - Template with example values
- **`config.php`** - Your actual configuration (create from example)

### Environment Variables

You can override config values using environment variables:

```bash
# Set NWC URI
export NWC_URI="nostr+walletconnect://your-wallet-details"

# Enable verbose output
export NWC_VERBOSE=true

# Set custom amounts
export NWC_SMALL_AMOUNT=50000  # 50 sats in msats

# Run example
php client-example.php
```

### Available Settings

| Setting | Environment Variable | Default | Description |
|---------|---------------------|---------|-------------|
| `nwc_uri` | `NWC_URI` | Example URI | Your wallet connection string |
| `verbose` | `NWC_VERBOSE` | `false` | Enable detailed output |
| `timeout` | `NWC_TIMEOUT` | `30` | Command timeout (seconds) |
| `lookback_seconds` | `NWC_LOOKBACK_SECONDS` | `300` | Notification lookback period |
| `test_amount` | `NWC_TEST_AMOUNT` | `1000` | Small test amount (1 sat) |
| `small_amount` | `NWC_SMALL_AMOUNT` | `21000` | Medium test amount (21 sats) |
| `medium_amount` | `NWC_MEDIUM_AMOUNT` | `100000` | Larger test amount (100 sats) |

## Examples Overview

### Core Examples
- **`client-example.php`** - Comprehensive NWC client demonstration showing wallet connection, info retrieval, balance checking, invoice creation, and transaction listing
- **`get-info-command.php`** - Simple example focused on wallet info and balance checking
- **`uri-parsing-example.php`** - NWC URI parsing, validation, generation, and error handling demonstration

### Payment Examples
- **`payment-flow-example.php`** - Complete payment workflow including invoice creation, lookup, and payment flow structure (with safe payment section commented out)

### Notification Examples
- **`notification-listener.php`** - Real-time WebSocket notification listener that monitors for payment_received and payment_sent notifications with custom callbacks

## Security Notes

⚠️ **Important**: Never commit your actual `config.php` file to version control!

- The `config.php` file contains your wallet credentials
- Only commit `config.php.example` with placeholder values
- Use environment variables in production environments
- Test with small amounts on testnet when possible

## Troubleshooting

### Common Issues

**"Could not connect to wallet"**
- Verify your NWC URI is correct
- Check that the relay is accessible
- Ensure your secret key is valid

**"Method not supported"**
- Check wallet capabilities with `get-info-command.php`
- Some methods may not be available on all wallets

**"Insufficient balance"**
- Verify wallet has funds for payments
- Check amounts are in millisatoshis (1 sat = 1000 msats)

### Getting Help

1. Run examples with verbose output: `NWC_VERBOSE=true php example.php`
2. Check your wallet's supported methods with `get-info-command.php`
3. Verify NWC URI format and credentials with `uri-parsing-example.php`
4. Test with smaller amounts first 