<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Multi Pay Keysend command implementation.
 *
 * Requests multiple keysend payments to destination pubkeys.
 */
class MultiPayKeysendCommand extends AbstractCommand
{
    /**
     * Create a new multi pay keysend command.
     *
     * @param array $keysends Array of keysend data, each containing 'destination', 'amount', and optionally 'preimage' and 'tlv_records'
     */
    public function __construct(array $keysends)
    {
        $this->setParam('keysends', $keysends);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'multi_pay_keysend';
    }

    /**
     * Get the keysends array.
     *
     * @return array
     */
    public function getKeysends(): array
    {
        return $this->getParam('keysends', []);
    }

    /**
     * Add a keysend to the list.
     *
     * @param string $destination The destination pubkey (hex)
     * @param int $amount Amount in millisatoshis
     * @param string|null $preimage Optional preimage (hex)
     * @param array $tlvRecords Optional TLV records
     * @return static
     */
    public function addKeysend(
        string $destination,
        int $amount,
        ?string $preimage = null,
        array $tlvRecords = [],
    ): static {
        $keysends = $this->getKeysends();
        $keysendData = [
            'destination' => $destination,
            'amount' => $amount,
        ];

        if ($preimage !== null) {
            $keysendData['preimage'] = $preimage;
        }

        if (!empty($tlvRecords)) {
            $keysendData['tlv_records'] = $tlvRecords;
        }

        $keysends[] = $keysendData;
        return $this->setParam('keysends', $keysends);
    }

    /**
     * Set the keysends array.
     *
     * @param array $keysends
     * @return static
     */
    public function setKeysends(array $keysends): static
    {
        return $this->setParam('keysends', $keysends);
    }

    /**
     * Get the number of keysends.
     *
     * @return int
     */
    public function getKeysendCount(): int
    {
        return count($this->getKeysends());
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $keysends = $this->getKeysends();

        if (empty($keysends)) {
            throw new CommandException("At least one keysend is required");
        }

        foreach ($keysends as $index => $keysendData) {
            if (!is_array($keysendData)) {
                throw new CommandException("Keysend data at index {$index} must be an array");
            }

            if (!isset($keysendData['destination']) || empty($keysendData['destination'])) {
                throw new CommandException("Destination is required at index {$index}");
            }

            $destination = $keysendData['destination'];
            if (!ctype_xdigit($destination) || strlen($destination) !== 66) {
                throw new CommandException("Destination must be a valid 33-byte hex pubkey at index {$index}");
            }

            if (!isset($keysendData['amount']) || $keysendData['amount'] <= 0) {
                throw new CommandException("Amount must be positive at index {$index}");
            }

            if (isset($keysendData['preimage'])) {
                $preimage = $keysendData['preimage'];
                if (!ctype_xdigit($preimage) || strlen($preimage) !== 64) {
                    throw new CommandException("Preimage must be a valid 32-byte hex string at index {$index}");
                }
            }
        }

        return true;
    }
}
