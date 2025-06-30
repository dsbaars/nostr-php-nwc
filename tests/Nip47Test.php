<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use dsbaars\nostr\Nip47\NwcUri;
use dsbaars\nostr\Nip47\Exception\InvalidUriException;

class Nip47Test extends TestCase
{
    private string $validWalletPubkey;
    private string $validSecret;
    private array $validRelays;
    private string $validLud16;
    private string $validNwcUri;

    protected function setUp(): void
    {
        $this->validWalletPubkey = 'b889ff5b1513b641e2a139f661a661364979c5beee91842f8f0ef42ab558e9d4';
        $this->validSecret = '71a8c14c1407c113601079c4302dab36460f0ccd0ad506f1f2dc73b5100e4f3c';
        $this->validRelays = ['wss://relay.damus.io', 'wss://relay.getalby.com/v1'];
        $this->validLud16 = 'wallet@example.com';

        $this->validNwcUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0])
            . '&secret=' . $this->validSecret
            . '&lud16=' . urlencode($this->validLud16);
    }

    #[Test]
    public function testValidUriParsing(): void
    {
        $nwcUri = new NwcUri($this->validNwcUri);

        $this->assertEquals($this->validWalletPubkey, $nwcUri->getWalletPubkey());
        $this->assertEquals($this->validSecret, $nwcUri->getSecret());
        $this->assertContains($this->validRelays[0], $nwcUri->getRelays());
        $this->assertEquals($this->validLud16, $nwcUri->getLud16());
        $this->assertTrue($nwcUri->validate());
    }

    #[Test]
    public function testUriParsingWithoutOptionalParameters(): void
    {
        $basicUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0])
            . '&secret=' . $this->validSecret;

        $nwcUri = new NwcUri($basicUri);

        $this->assertEquals($this->validWalletPubkey, $nwcUri->getWalletPubkey());
        $this->assertEquals($this->validSecret, $nwcUri->getSecret());
        $this->assertContains($this->validRelays[0], $nwcUri->getRelays());
        $this->assertNull($nwcUri->getLud16());
        $this->assertTrue($nwcUri->validate());
    }

    #[Test]
    public function testUriParsingWithMultipleRelays(): void
    {
        $multiRelayUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0])
            . '&relay=' . urlencode($this->validRelays[1])
            . '&secret=' . $this->validSecret;

        $nwcUri = new NwcUri($multiRelayUri);

        $relays = $nwcUri->getRelays();
        $this->assertCount(2, $relays);
        $this->assertContains($this->validRelays[0], $relays);
        $this->assertContains($this->validRelays[1], $relays);
    }

    #[Test]
    public function testUriGeneration(): void
    {
        $generatedUri = NwcUri::generate(
            $this->validWalletPubkey,
            [$this->validRelays[0]],
            $this->validSecret,
            $this->validLud16,
        );

        // Parse the generated URI to verify it's valid
        $nwcUri = new NwcUri($generatedUri);

        $this->assertEquals($this->validWalletPubkey, $nwcUri->getWalletPubkey());
        $this->assertEquals($this->validSecret, $nwcUri->getSecret());
        $this->assertContains($this->validRelays[0], $nwcUri->getRelays());
        $this->assertEquals($this->validLud16, $nwcUri->getLud16());
    }

    #[Test]
    public function testUriGenerationWithMultipleRelays(): void
    {
        $generatedUri = NwcUri::generate(
            $this->validWalletPubkey,
            $this->validRelays,
            $this->validSecret,
        );

        $nwcUri = new NwcUri($generatedUri);
        $relays = $nwcUri->getRelays();

        $this->assertCount(2, $relays);
        $this->assertContains($this->validRelays[0], $relays);
        $this->assertContains($this->validRelays[1], $relays);
    }

    #[Test]
    public function testToArrayConversion(): void
    {
        $nwcUri = new NwcUri($this->validNwcUri);
        $array = $nwcUri->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($this->validWalletPubkey, $array['wallet_pubkey']);
        $this->assertEquals($this->validSecret, $array['secret']);
        $this->assertContains($this->validRelays[0], $array['relays']);
        $this->assertEquals($this->validLud16, $array['lud16']);
    }

    #[Test]
    public function testToStringConversion(): void
    {
        $nwcUri = new NwcUri($this->validNwcUri);
        $reconstructed = $nwcUri->__toString();

        // Parse the reconstructed URI to verify it's valid
        $newNwcUri = new NwcUri($reconstructed);

        $this->assertEquals($nwcUri->getWalletPubkey(), $newNwcUri->getWalletPubkey());
        $this->assertEquals($nwcUri->getSecret(), $newNwcUri->getSecret());
        $this->assertEquals($nwcUri->getRelays(), $newNwcUri->getRelays());
        $this->assertEquals($nwcUri->getLud16(), $newNwcUri->getLud16());
    }

    #[Test]
    public function testInvalidProtocol(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("URI must start with 'nostr+walletconnect://'");

        new NwcUri('invalid://test-uri');
    }

    #[Test]
    public function testMissingQueryParameters(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("URI must contain query parameters");

        new NwcUri('nostr+walletconnect://' . $this->validWalletPubkey);
    }

    #[Test]
    public function testInvalidWalletPubkeyFormat(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid wallet public key format");

        $invalidUri = 'nostr+walletconnect://invalid-pubkey?relay=' . urlencode($this->validRelays[0]) . '&secret=' . $this->validSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testShortWalletPubkey(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid wallet public key format");

        $shortPubkey = 'abc123';
        $invalidUri = 'nostr+walletconnect://' . $shortPubkey . '?relay=' . urlencode($this->validRelays[0]) . '&secret=' . $this->validSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testMissingRelayParameter(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Missing required 'relay' parameter");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey . '?secret=' . $this->validSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testMissingSecretParameter(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Missing required 'secret' parameter");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey . '?relay=' . urlencode($this->validRelays[0]);
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testInvalidRelayUrl(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid relay URL: http://insecure.relay");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=http://insecure.relay&secret=' . $this->validSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testNonWssRelayUrl(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid relay URL: ws://websocket.relay");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=ws://websocket.relay&secret=' . $this->validSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testInvalidSecretFormat(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid secret format");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0]) . '&secret=tooshort';
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testNonHexSecret(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid secret format");

        $nonHexSecret = str_repeat('z', 64); // 64 characters but not hex
        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0]) . '&secret=' . $nonHexSecret;
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testInvalidLud16Format(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid lud16 format");

        $invalidUri = 'nostr+walletconnect://' . $this->validWalletPubkey
            . '?relay=' . urlencode($this->validRelays[0])
            . '&secret=' . $this->validSecret
            . '&lud16=invalid-email-format';
        new NwcUri($invalidUri);
    }

    #[Test]
    public function testValidLud16Formats(): void
    {
        $validLud16s = [
            'user@domain.com',
            'test.user@example.org',
            'lightning@wallet.provider.io',
            'payments@sub.domain.co.uk',
        ];

        foreach ($validLud16s as $lud16) {
            $uri = 'nostr+walletconnect://' . $this->validWalletPubkey
                . '?relay=' . urlencode($this->validRelays[0])
                . '&secret=' . $this->validSecret
                . '&lud16=' . urlencode($lud16);

            $nwcUri = new NwcUri($uri);
            $this->assertEquals($lud16, $nwcUri->getLud16());
        }
    }

    #[Test]
    public function testCaseInsensitiveHexValues(): void
    {
        $uppercasePubkey = strtoupper($this->validWalletPubkey);
        $uppercaseSecret = strtoupper($this->validSecret);

        $uri = 'nostr+walletconnect://' . $uppercasePubkey
            . '?relay=' . urlencode($this->validRelays[0])
            . '&secret=' . $uppercaseSecret;

        $nwcUri = new NwcUri($uri);

        // Should accept both cases and store as provided
        $this->assertEquals($uppercasePubkey, $nwcUri->getWalletPubkey());
        $this->assertEquals($uppercaseSecret, $nwcUri->getSecret());
        $this->assertTrue($nwcUri->validate());
    }

    #[Test]
    public function testGenerationWithInvalidInputs(): void
    {
        // Invalid wallet pubkey
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid wallet public key format");

        NwcUri::generate('invalid-pubkey', $this->validRelays, $this->validSecret);
    }

    #[Test]
    public function testGenerationWithEmptyRelays(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("At least one relay is required");

        NwcUri::generate($this->validWalletPubkey, [], $this->validSecret);
    }

    #[Test]
    public function testGenerationWithInvalidSecret(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid secret format");

        NwcUri::generate($this->validWalletPubkey, $this->validRelays, 'invalid-secret');
    }

    #[Test]
    public function testGenerationWithInvalidLud16(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid lud16 format");

        NwcUri::generate($this->validWalletPubkey, $this->validRelays, $this->validSecret, 'invalid-email');
    }

    #[Test]
    public function testGenerationWithInvalidRelayUrl(): void
    {
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Invalid relay URL: http://insecure.relay");

        NwcUri::generate($this->validWalletPubkey, ['http://insecure.relay'], $this->validSecret);
    }

    #[Test]
    public function testUrlEncodedParameters(): void
    {
        $specialCharRelay = 'wss://relay.example.com/path?param=value&other=test';
        $specialCharLud16 = 'test+user@example.com';

        $uri = NwcUri::generate(
            $this->validWalletPubkey,
            [$specialCharRelay],
            $this->validSecret,
            $specialCharLud16,
        );

        $nwcUri = new NwcUri($uri);

        $this->assertContains($specialCharRelay, $nwcUri->getRelays());
        $this->assertEquals($specialCharLud16, $nwcUri->getLud16());
    }

    #[Test]
    public function testEmptyNwcUriConstructor(): void
    {
        $nwcUri = new NwcUri();

        // Should be able to create empty instance
        $this->assertInstanceOf(NwcUri::class, $nwcUri);

        // But validation should fail
        $this->expectException(InvalidUriException::class);
        $this->expectExceptionMessage("Wallet public key is required");
        $nwcUri->validate();
    }

    #[Test]
    public function testValidationWithValidUri(): void
    {
        $nwcUri = new NwcUri($this->validNwcUri);

        // Should not throw any exception
        $isValid = $nwcUri->validate();
        $this->assertTrue($isValid);
    }

    #[Test]
    public function testValidationFailureMessages(): void
    {
        $nwcUri = new NwcUri();

        // Test each validation failure
        $validationErrors = [
            'Wallet public key is required',
            'At least one relay is required',
            'Secret is required',
        ];

        foreach ($validationErrors as $expectedMessage) {
            try {
                $nwcUri->validate();
                $this->fail('Expected InvalidUriException was not thrown');
            } catch (InvalidUriException $e) {
                $this->assertEquals($expectedMessage, $e->getMessage());
                break; // Only check the first error message
            }
        }
    }

    #[Test]
    public function testRealWorldNwcUris(): void
    {
        // Test with real-world-like NWC URIs (anonymized with valid 64-char pubkeys)
        $realWorldUris = [
            'nostr+walletconnect://4ac16ad3791d34e735cded8d2d1865fa05c295775e5994bb25ac0056b3197c86?relay=wss://relay.getalby.com/v1&secret=ef823148eb754944fea5b1eb00963c35d9787225b0ba15490fcaa0dc46b4eed2',
            'nostr+walletconnect://a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4?relay=wss://relay.damus.io&relay=wss://relay.nostr.info&secret=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef&lud16=user@getalby.com',
        ];

        foreach ($realWorldUris as $uri) {
            $nwcUri = new NwcUri($uri);

            $this->assertTrue($nwcUri->validate());
            $this->assertNotEmpty($nwcUri->getWalletPubkey());
            $this->assertNotEmpty($nwcUri->getSecret());
            $this->assertNotEmpty($nwcUri->getRelays());

            // Test round-trip
            $reconstructed = $nwcUri->__toString();
            $newNwcUri = new NwcUri($reconstructed);
            $this->assertTrue($newNwcUri->validate());
        }
    }
}
