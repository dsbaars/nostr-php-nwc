<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use dsbaars\nostr\Nip47\Command\GetBalanceCommand;
use dsbaars\nostr\Nip47\Command\GetInfoCommand;
use dsbaars\nostr\Nip47\Command\PayInvoiceCommand;
use dsbaars\nostr\Nip47\Command\MakeInvoiceCommand;
use dsbaars\nostr\Nip47\Command\LookupInvoiceCommand;
use dsbaars\nostr\Nip47\Response\GetBalanceResponse;
use dsbaars\nostr\Nip47\Response\GetInfoResponse;
use dsbaars\nostr\Nip47\Response\PayInvoiceResponse;
use dsbaars\nostr\Nip47\Response\MakeInvoiceResponse;
use dsbaars\nostr\Nip47\Response\LookupInvoiceResponse;
use dsbaars\nostr\Nip47\ErrorCode;
use dsbaars\nostr\Nip47\Exception\CommandException;
use dsbaars\nostr\Nip47\Exception\PaymentFailedException;
use dsbaars\nostr\Nip47\Exception\InsufficientBalanceException;

class Nip47CommandTest extends TestCase
{
    #[Test]
    public function testGetBalanceCommand(): void
    {
        $command = new GetBalanceCommand();

        $this->assertEquals('get_balance', $command->getMethod());
        $this->assertEmpty($command->getParams());
        $this->assertTrue($command->validate());

        $array = $command->toArray();
        $this->assertEquals('get_balance', $array['method']);
        $this->assertEmpty($array['params']);
    }

    #[Test]
    public function testGetInfoCommand(): void
    {
        $command = new GetInfoCommand();

        $this->assertEquals('get_info', $command->getMethod());
        $this->assertEmpty($command->getParams());
        $this->assertTrue($command->validate());

        $array = $command->toArray();
        $this->assertEquals('get_info', $array['method']);
        $this->assertEmpty($array['params']);
    }

    #[Test]
    public function testPayInvoiceCommand(): void
    {
        $invoice = 'lnbc1500n1pn2s396pp5urq5jup60f6xqwgkgj9s8l8upd22k5xgvzr23v3lw4kjs9l2svuqsp5h9v3r5rqj6kf6pk2s2c5u3mawg5erjjkhfk5r9jrchmvnktewq2qxqzjccqpjrzjqd6pvhgl6pxpqtq5vvt35wqgx7w6t5zqczd4h6xz7hg9mjr8u9zsxjqegqmvjqv5qqqqqqqqqqqqjqmvjqy7qjgj3r';
        $amount = 1500;

        $command = new PayInvoiceCommand($invoice, $amount);

        $this->assertEquals('pay_invoice', $command->getMethod());
        $this->assertEquals($invoice, $command->getInvoice());
        $this->assertEquals($amount, $command->getAmount());
        $this->assertTrue($command->validate());

        $params = $command->getParams();
        $this->assertEquals($invoice, $params['invoice']);
        $this->assertEquals($amount, $params['amount']);
    }

    #[Test]
    public function testPayInvoiceCommandWithoutAmount(): void
    {
        $invoice = 'lnbc1500n1pn2s396pp5urq5jup60f6xqwgkgj9s8l8upd22k5xgvzr23v3lw4kjs9l2svuqsp5h9v3r5rqj6kf6pk2s2c5u3mawg5erjjkhfk5r9jrchmvnktewq2qxqzjccqpjrzjqd6pvhgl6pxpqtq5vvt35wqgx7w6t5zqczd4h6xz7hg9mjr8u9zsxjqegqmvjqv5qqqqqqqqqqqqjqmvjqy7qjgj3r';

        $command = new PayInvoiceCommand($invoice);

        $this->assertEquals($invoice, $command->getInvoice());
        $this->assertNull($command->getAmount());
        $this->assertTrue($command->validate());
    }

    #[Test]
    public function testPayInvoiceCommandValidation(): void
    {
        // Test empty invoice
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Invoice is required");

        $command = new PayInvoiceCommand('');
        $command->validate();
    }

    #[Test]
    public function testPayInvoiceCommandInvalidFormat(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Invalid invoice format");

        $command = new PayInvoiceCommand('invalid-invoice-format');
        $command->validate();
    }

    #[Test]
    public function testPayInvoiceCommandNegativeAmount(): void
    {
        $invoice = 'lnbc1500n1pn2s396pp5urq5jup60f6xqwgkgj9s8l8upd22k5xgvzr23v3lw4kjs9l2svuqsp5h9v3r5rqj6kf6pk2s2c5u3mawg5erjjkhfk5r9jrchmvnktewq2qxqzjccqpjrzjqd6pvhgl6pxpqtq5vvt35wqgx7w6t5zqczd4h6xz7hg9mjr8u9zsxjqegqmvjqv5qqqqqqqqqqqqjqmvjqy7qjgj3r';

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Amount must be positive");

        $command = new PayInvoiceCommand($invoice, -100);
        $command->validate();
    }

    #[Test]
    public function testMakeInvoiceCommand(): void
    {
        $amount = 1000;
        $description = 'Test invoice';
        $expiry = 3600;

        $command = new MakeInvoiceCommand($amount, $description, null, $expiry);

        $this->assertEquals('make_invoice', $command->getMethod());
        $this->assertEquals($amount, $command->getAmount());
        $this->assertEquals($description, $command->getDescription());
        $this->assertNull($command->getDescriptionHash());
        $this->assertEquals($expiry, $command->getExpiry());
        $this->assertTrue($command->validate());
    }

    #[Test]
    public function testMakeInvoiceCommandWithDescriptionHash(): void
    {
        $amount = 1000;
        $descriptionHash = 'a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4';

        $command = new MakeInvoiceCommand($amount, null, $descriptionHash);

        $this->assertEquals($amount, $command->getAmount());
        $this->assertNull($command->getDescription());
        $this->assertEquals($descriptionHash, $command->getDescriptionHash());
        $this->assertTrue($command->validate());
    }

    #[Test]
    public function testMakeInvoiceCommandInvalidAmount(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Amount must be positive");

        $command = new MakeInvoiceCommand(-100);
        $command->validate();
    }

    #[Test]
    public function testMakeInvoiceCommandInvalidExpiry(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Expiry must be positive");

        $command = new MakeInvoiceCommand(1000, 'Test', null, -60);
        $command->validate();
    }

    #[Test]
    public function testMakeInvoiceCommandBothDescriptions(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Cannot specify both description and description_hash");

        $command = new MakeInvoiceCommand(1000, 'Test description', 'hash123');
        $command->validate();
    }

    #[Test]
    public function testLookupInvoiceCommand(): void
    {
        $paymentHash = 'a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4';
        $invoice = 'lnbc1500n1pn2s396pp5urq5jup60f6xqwgkgj9s8l8upd22k5xgvzr23v3lw4kjs9l2svuqsp5h9v3r5rqj6kf6pk2s2c5u3mawg5erjjkhfk5r9jrchmvnktewq2qxqzjccqpjrzjqd6pvhgl6pxpqtq5vvt35wqgx7w6t5zqczd4h6xz7hg9mjr8u9zsxjqegqmvjqv5qqqqqqqqqqqqjqmvjqy7qjgj3r';

        // Test with payment hash
        $command = new LookupInvoiceCommand($paymentHash);
        $this->assertEquals('lookup_invoice', $command->getMethod());
        $this->assertEquals($paymentHash, $command->getPaymentHash());
        $this->assertNull($command->getInvoice());
        $this->assertTrue($command->validate());

        // Test with invoice
        $command2 = new LookupInvoiceCommand(null, $invoice);
        $this->assertNull($command2->getPaymentHash());
        $this->assertEquals($invoice, $command2->getInvoice());
        $this->assertTrue($command2->validate());
    }

    #[Test]
    public function testLookupInvoiceCommandValidation(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Either payment_hash or invoice is required");

        $command = new LookupInvoiceCommand();
        $command->validate();
    }

    #[Test]
    public function testLookupInvoiceCommandInvalidPaymentHash(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage("Invalid payment hash format");

        $command = new LookupInvoiceCommand('invalid-hash');
        $command->validate();
    }

    #[Test]
    public function testGetBalanceResponse(): void
    {
        $data = [
            'result_type' => 'get_balance',
            'result' => ['balance' => 100000],
            'error' => null,
        ];

        $response = GetBalanceResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isError());
        $this->assertEquals(100000, $response->getBalance());
        $this->assertEquals(100.0, $response->getBalanceInSats());
        $this->assertEquals(0.000001, $response->getBalanceInBtc());
    }

    #[Test]
    public function testGetInfoResponse(): void
    {
        $data = [
            'result_type' => 'get_info',
            'result' => [
                'alias' => 'Test Wallet',
                'color' => '#FF5733',
                'pubkey' => 'a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4',
                'network' => 'mainnet',
                'block_height' => 800000,
                'block_hash' => 'hash123',
                'methods' => ['get_balance', 'pay_invoice', 'make_invoice'],
                'notifications' => ['payment_received'],
            ],
            'error' => null,
        ];

        $response = GetInfoResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('Test Wallet', $response->getAlias());
        $this->assertEquals('#FF5733', $response->getColor());
        $this->assertEquals('mainnet', $response->getNetwork());
        $this->assertEquals(800000, $response->getBlockHeight());
        $this->assertTrue($response->supportsMethod('get_balance'));
        $this->assertFalse($response->supportsMethod('unsupported_method'));
        $this->assertTrue($response->supportsNotifications());
        $this->assertTrue($response->supportsNotification('payment_received'));
        $this->assertFalse($response->supportsNotification('payment_sent'));
    }

    #[Test]
    public function testPayInvoiceResponse(): void
    {
        $data = [
            'result_type' => 'pay_invoice',
            'result' => [
                'preimage' => 'preimage123',
                'fees_paid' => 100,
            ],
            'error' => null,
        ];

        $response = PayInvoiceResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isPaymentSuccessful());
        $this->assertEquals('preimage123', $response->getPreimage());
        $this->assertEquals(100, $response->getFeesPaid());
    }

    #[Test]
    public function testMakeInvoiceResponse(): void
    {
        $data = [
            'result_type' => 'make_invoice',
            'result' => [
                'type' => 'incoming',
                'invoice' => 'lnbc1500n1...',
                'description' => 'Test invoice',
                'payment_hash' => 'hash123',
                'amount' => 1500,
                'created_at' => time(),
                'expires_at' => time() + 3600,
            ],
            'error' => null,
        ];

        $response = MakeInvoiceResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isIncoming());
        $this->assertFalse($response->isExpired());
        $this->assertEquals('lnbc1500n1...', $response->getInvoice());
        $this->assertEquals('Test invoice', $response->getDescription());
        $this->assertEquals(1500, $response->getAmount());
    }

    #[Test]
    public function testLookupInvoiceResponse(): void
    {
        $data = [
            'result_type' => 'lookup_invoice',
            'result' => [
                'type' => 'outgoing',
                'invoice' => 'lnbc1500n1...',
                'payment_hash' => 'hash123',
                'amount' => 1500,
                'settled_at' => time(),
            ],
            'error' => null,
        ];

        $response = LookupInvoiceResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isOutgoing());
        $this->assertFalse($response->isIncoming());
        $this->assertTrue($response->isSettled());
        $this->assertEquals(1500, $response->getAmount());
    }

    #[Test]
    public function testErrorResponse(): void
    {
        $data = [
            'result_type' => 'pay_invoice',
            'result' => null,
            'error' => [
                'code' => 'PAYMENT_FAILED',
                'message' => 'Payment could not be completed',
            ],
        ];

        $response = PayInvoiceResponse::fromArray($data);

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isError());
        $this->assertEquals('PAYMENT_FAILED', $response->getErrorCode());
        $this->assertEquals('Payment could not be completed', $response->getErrorMessage());

        // Test exception throwing
        $this->expectException(PaymentFailedException::class);
        $response->throwIfError();
    }

    #[Test]
    public function testErrorCodeEnum(): void
    {
        // Test error code messages
        $this->assertEquals(
            'The client is sending commands too fast. It should retry in a few seconds.',
            ErrorCode::RATE_LIMITED->getMessage(),
        );

        $this->assertEquals(
            'The wallet does not have enough funds to cover a fee reserve or the payment amount.',
            ErrorCode::INSUFFICIENT_BALANCE->getMessage(),
        );

        // Test exception creation
        $exception = ErrorCode::PAYMENT_FAILED->createException();
        $this->assertInstanceOf(PaymentFailedException::class, $exception);

        $exception = ErrorCode::INSUFFICIENT_BALANCE->createException();
        $this->assertInstanceOf(InsufficientBalanceException::class, $exception);

        $exception = ErrorCode::NOT_IMPLEMENTED->createException();
        $this->assertInstanceOf(CommandException::class, $exception);
    }

    #[Test]
    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom error message';
        $exception = ErrorCode::PAYMENT_FAILED->createException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertInstanceOf(PaymentFailedException::class, $exception);
    }

    #[Test]
    public function testInsufficientBalanceResponse(): void
    {
        $data = [
            'result_type' => 'pay_invoice',
            'result' => null,
            'error' => [
                'code' => 'INSUFFICIENT_BALANCE',
                'message' => 'Not enough funds in wallet',
            ],
        ];

        $response = PayInvoiceResponse::fromArray($data);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Not enough funds in wallet');
        $response->throwIfError();
    }

    #[Test]
    public function testUnknownErrorCode(): void
    {
        $data = [
            'result_type' => 'get_balance',
            'result' => null,
            'error' => [
                'code' => 'UNKNOWN_ERROR',
                'message' => 'Something went wrong',
            ],
        ];

        $response = GetBalanceResponse::fromArray($data);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Something went wrong');
        $response->throwIfError();
    }

    #[Test]
    public function testTestnetInvoiceFormats(): void
    {
        $testnetInvoice = 'lntb1500n1pn2s396pp5urq5jup60f6xqwgkgj9s8l8upd22k5xgvzr23v3lw4kjs9l2svuq';

        $command = new PayInvoiceCommand($testnetInvoice);
        $this->assertTrue($command->validate());

        $lookupCommand = new LookupInvoiceCommand(null, $testnetInvoice);
        $this->assertTrue($lookupCommand->validate());
    }
}
