<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Pay Keysend command implementation.
 *
 * Requests a keysend payment to a destination pubkey.
 */
class PayKeysendCommand extends AbstractCommand
{
    /**
     * Create a new pay keysend command.
     *
     * @param string $destination The destination pubkey (hex)
     * @param int $amount Amount in millisatoshis
     * @param string|null $preimage Optional preimage (hex), if not provided, a random one will be generated
     * @param array $tlvRecords Optional TLV records as key-value pairs
     */
    public function __construct(
        string $destination,
        int $amount,
        ?string $preimage = null,
        array $tlvRecords = [],
    ) {
        $this->setParam('destination', $destination);
        $this->setParam('amount', $amount);

        if ($preimage !== null) {
            $this->setParam('preimage', $preimage);
        }

        if (!empty($tlvRecords)) {
            $this->setParam('tlv_records', $tlvRecords);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'pay_keysend';
    }

    /**
     * Get the destination pubkey.
     *
     * @return string
     */
    public function getDestination(): string
    {
        return $this->getParam('destination');
    }

    /**
     * Get the amount in millisatoshis.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->getParam('amount');
    }

    /**
     * Get the preimage.
     *
     * @return string|null
     */
    public function getPreimage(): ?string
    {
        return $this->getParam('preimage');
    }

    /**
     * Get the TLV records.
     *
     * @return array
     */
    public function getTlvRecords(): array
    {
        return $this->getParam('tlv_records', []);
    }

    /**
     * Set the preimage.
     *
     * @param string $preimage
     * @return static
     */
    public function setPreimage(string $preimage): static
    {
        return $this->setParam('preimage', $preimage);
    }

    /**
     * Set TLV records.
     *
     * @param array $tlvRecords
     * @return static
     */
    public function setTlvRecords(array $tlvRecords): static
    {
        return $this->setParam('tlv_records', $tlvRecords);
    }

    /**
     * Add a TLV record.
     *
     * @param string $type
     * @param string $value
     * @return static
     */
    public function addTlvRecord(string $type, string $value): static
    {
        $records = $this->getTlvRecords();
        $records[$type] = $value;
        return $this->setTlvRecords($records);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $destination = $this->getDestination();
        if (empty($destination)) {
            throw new CommandException("Destination is required");
        }

        if (!ctype_xdigit($destination) || strlen($destination) !== 66) {
            throw new CommandException("Destination must be a valid 33-byte hex pubkey");
        }

        $amount = $this->getAmount();
        if ($amount <= 0) {
            throw new CommandException("Amount must be positive");
        }

        $preimage = $this->getPreimage();
        if ($preimage !== null && (!ctype_xdigit($preimage) || strlen($preimage) !== 64)) {
            throw new CommandException("Preimage must be a valid 32-byte hex string");
        }

        return true;
    }
}
