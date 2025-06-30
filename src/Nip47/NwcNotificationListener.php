<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;
use dsbaars\nostr\Nip47\Event\NotificationEvent;
use dsbaars\nostr\Nip47\Notification\NotificationFactory;
use dsbaars\nostr\Nip47\Notification\PaymentReceivedNotification;
use dsbaars\nostr\Nip47\Notification\PaymentSentNotification;
use dsbaars\nostr\Nip47\Exception\NwcException;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\Encryption\Nip04;
use Psr\Log\AbstractLogger;

/**
 * Nostr Wallet Connect Notification Listener.
 *
 * Listens for real-time payment notifications from NWC wallet services
 * using WebSocket connections and triggers callbacks for different notification types.
 */
class NwcNotificationListener
{
    private NwcClient $client;
    private ?AbstractLogger $logger;
    private $onPaymentReceived = null;
    private $onPaymentSent = null;
    private bool $running = false;
    private bool $verbose;
    private int $lookbackSeconds;

    /**
     * Create a new NWC notification listener.
     *
     * @param NwcClient $client The NWC client instance
     * @param AbstractLogger|null $logger Optional logger for debug output
     * @param bool $verbose Whether to output verbose messages to console
     * @param int $lookbackSeconds How many seconds to look back for notifications
     */
    public function __construct(
        NwcClient $client,
        ?AbstractLogger $logger = null,
        bool $verbose = false,
        int $lookbackSeconds = 60,
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->verbose = $verbose;
        $this->lookbackSeconds = $lookbackSeconds;
    }

    /**
     * Set callback for payment received notifications.
     *
     * @param callable $callback Callback function that receives (PaymentReceivedNotification, \stdClass $event)
     * @return $this
     */
    public function onPaymentReceived(callable $callback): self
    {
        $this->onPaymentReceived = $callback;
        return $this;
    }

    /**
     * Set callback for payment sent notifications.
     *
     * @param callable $callback Callback function that receives (PaymentSentNotification, \stdClass $event)
     * @return $this
     */
    public function onPaymentSent(callable $callback): self
    {
        $this->onPaymentSent = $callback;
        return $this;
    }

    /**
     * Start listening for notifications indefinitely.
     *
     * @throws NwcException If no relays are available or connection fails
     */
    public function listen(): void
    {
        $this->running = true;

        $this->logger?->info("Starting NWC notification listener");
        if ($this->verbose) {
            echo "Starting WebSocket notification listener..." . PHP_EOL;
            echo "Press Ctrl+C to stop" . PHP_EOL . PHP_EOL;
        }

        // Get the first relay
        $relays = $this->client->getNwcUri()->getRelays();
        if (empty($relays)) {
            throw new NwcException("No relays available");
        }

        $relayUrl = $relays[0];
        $this->logger?->info("Connecting to relay", ['relay' => $relayUrl]);
        if ($this->verbose) {
            echo "Connecting to relay: " . $relayUrl . PHP_EOL;
        }

        // Set up notification filter
        $filter = $this->client->getNotificationFilter();
        $filter->setSince(time() - $this->lookbackSeconds);

        $subscription = new Subscription();
        $subscriptionMessage = new RequestMessage($subscription->getId(), [$filter]);

        $this->logger?->debug("Created notification subscription", [
            'subscription_id' => $subscription->getId(),
            'kinds' => $filter->kinds ?? [],
            'authors' => $filter->authors ?? [],
            'ptags' => $filter->ptags ?? [],
            'lookback_seconds' => $this->lookbackSeconds,
        ]);

        if ($this->verbose) {
            echo "Created subscription for notifications:" . PHP_EOL;
            echo "  - Subscription ID: " . $subscription->getId() . PHP_EOL;
            echo "  - Kinds: [" . implode(', ', $filter->kinds ?? []) . "]" . PHP_EOL;
            echo "  - Authors: [" . implode(', ', $filter->authors ?? []) . "]" . PHP_EOL;
            echo "  - P tags: [" . implode(', ', $filter->ptags ?? []) . "]" . PHP_EOL . PHP_EOL;
        }

        try {
            $this->connectAndListen($relayUrl, $subscriptionMessage);
        } catch (\Exception $e) {
            $this->logger?->error("Error in notification listener", ['error' => $e->getMessage()]);
            throw new NwcException("Failed to start notification listener: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Stop the notification listener.
     */
    public function stop(): void
    {
        $this->running = false;
        $this->logger?->info("Stopping notification listener");
        if ($this->verbose) {
            echo PHP_EOL . "Stopping notification listener..." . PHP_EOL;
        }
    }

    /**
     * Check if the listener is currently running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Connect to WebSocket and listen indefinitely.
     *
     * @param string $relayUrl The relay URL to connect to
     * @param RequestMessage $subscriptionMessage The subscription message to send
     * @throws \Exception If WebSocket connection fails
     */
    private function connectAndListen(string $relayUrl, RequestMessage $subscriptionMessage): void
    {
        // Set up Guzzle client with WebSocket middleware
        $handlerStack = new HandlerStack(new StreamHandler());
        $handlerStack->unshift(new WebSocketMiddleware());
        $guzzle = new Client(['handler' => $handlerStack]);

        $this->logger?->debug("Connecting to WebSocket", ['relay' => $relayUrl]);
        if ($this->verbose) {
            echo "Connecting to WebSocket..." . PHP_EOL;
        }

        // Connect to WebSocket
        $handshakeResponse = $guzzle->requestAsync('GET', $relayUrl)->wait();

        if ($handshakeResponse->getStatusCode() !== 101) {
            throw new \RuntimeException("WebSocket handshake failed: " . $handshakeResponse->getReasonPhrase());
        }

        /** @var \Valtzu\WebSocketMiddleware\WebSocketStream $ws */
        $ws = $handshakeResponse->getBody();

        $this->logger?->info("WebSocket connected successfully");
        if ($this->verbose) {
            echo "✓ WebSocket connected successfully" . PHP_EOL;
        }

        // Send subscription
        $subscriptionPayload = $subscriptionMessage->generate();
        $ws->write($subscriptionPayload);

        $this->logger?->debug("Subscription sent", ['payload' => $subscriptionPayload]);
        if ($this->verbose) {
            echo "✓ Subscription sent: " . $subscriptionPayload . PHP_EOL . PHP_EOL;
            echo "Listening for notifications..." . PHP_EOL;
            echo "=" . str_repeat("=", 50) . PHP_EOL;
        }

        // Listen indefinitely
        $messageBuffer = '';
        $readCount = 0;

        while ($this->running) {
            try {
                $wsContent = $ws->read();
                $readCount++;

                if ($wsContent !== '') {
                    $this->logger?->debug("Received WebSocket content", [
                        'read_count' => $readCount,
                        'content_length' => strlen($wsContent),
                    ]);

                    if ($this->verbose) {
                        echo "[Read #{$readCount}] Received WebSocket content (" . strlen($wsContent) . " bytes)" . PHP_EOL;
                    }

                    // Add to buffer
                    $messageBuffer .= $wsContent;

                    // Process complete messages from buffer
                    $this->processMessageBuffer($messageBuffer);
                } else {
                    // No content received, wait a bit
                    usleep(100000); // 100ms
                }
            } catch (\Exception $e) {
                $this->logger?->warning("Error reading from WebSocket", ['error' => $e->getMessage()]);
                if ($this->verbose) {
                    echo "Error reading from WebSocket: " . $e->getMessage() . PHP_EOL;
                }
                // Continue listening
                usleep(1000000); // 1 second
            }
        }

        $this->logger?->info("Notification listener stopped");
        if ($this->verbose) {
            echo "Notification listener stopped." . PHP_EOL;
        }
    }

    /**
     * Process message buffer to extract complete JSON messages and handle notifications.
     *
     * @param string $messageBuffer Buffer containing potentially fragmented JSON messages
     */
    private function processMessageBuffer(string &$messageBuffer): void
    {
        $bufferLength = strlen($messageBuffer);
        $bracketCount = 0;
        $inString = false;
        $escapeNext = false;
        $messageStart = 0;

        for ($i = 0; $i < $bufferLength; $i++) {
            $char = $messageBuffer[$i];

            if ($escapeNext) {
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if (!$inString) {
                if ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;

                    // Complete JSON array found
                    if ($bracketCount === 0) {
                        $jsonMessage = substr($messageBuffer, $messageStart, $i - $messageStart + 1);

                        $this->logger?->debug("Extracted complete JSON message", [
                            'content_length' => strlen($jsonMessage),
                        ]);

                        if ($this->verbose) {
                            echo "Extracted complete JSON message (" . strlen($jsonMessage) . " bytes)" . PHP_EOL;
                        }

                        try {
                            $messageData = json_decode($jsonMessage, false);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $this->handleRelayMessage($messageData);
                            } else {
                                $this->logger?->warning("Invalid JSON received", [
                                    'error' => json_last_error_msg(),
                                ]);
                                if ($this->verbose) {
                                    echo "Invalid JSON: " . json_last_error_msg() . PHP_EOL;
                                }
                            }
                        } catch (\Exception $e) {
                            $this->logger?->warning("Error processing message", ['error' => $e->getMessage()]);
                            if ($this->verbose) {
                                echo "Error processing message: " . $e->getMessage() . PHP_EOL;
                            }
                        }

                        // Remove processed part from buffer
                        $messageStart = $i + 1;
                        $messageBuffer = substr($messageBuffer, $i + 1);
                        $bufferLength = strlen($messageBuffer);
                        $i = -1; // Reset counter
                    }
                }
            }
        }
    }

    /**
     * Handle relay messages and process notification events.
     *
     * @param \stdClass|array $messageData The relay message data
     */
    private function handleRelayMessage(\stdClass|array $messageData): void
    {
        try {
            // Convert stdClass to array if needed for RelayResponse::create()
            $messageArray = is_array($messageData) ? $messageData : (array) $messageData;
            $relayResponse = RelayResponse::create($messageArray);

            $this->logger?->debug("Relay message received", ['type' => $relayResponse->type]);
            if ($this->verbose) {
                echo "Relay message type: " . $relayResponse->type . PHP_EOL;
            }

            if ($relayResponse->type === 'EVENT' && isset($relayResponse->event)) {
                if ($relayResponse->event->kind === NotificationEvent::KIND) {
                    $this->logger?->info("Notification event received", [
                        'event_id' => $relayResponse->event->id,
                        'created_at' => $relayResponse->event->created_at,
                    ]);

                    if ($this->verbose) {
                        echo "✓ Notification event received!" . PHP_EOL;
                        echo "  - Event ID: " . $relayResponse->event->id . PHP_EOL;
                        echo "  - Created: " . date('Y-m-d H:i:s', $relayResponse->event->created_at) . PHP_EOL;
                    }

                    $this->processNotificationEvent($relayResponse->event);
                } else {
                    $this->logger?->debug("Event with different kind received", [
                        'kind' => $relayResponse->event->kind,
                    ]);
                    if ($this->verbose) {
                        echo "Event with different kind: " . $relayResponse->event->kind . PHP_EOL;
                    }
                }
            } else {
                $this->logger?->debug("Non-event message received", [
                    'type' => $relayResponse->type ?? 'unknown',
                ]);
                if ($this->verbose) {
                    echo "Non-event message: " . ($relayResponse->type ?? 'unknown') . PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            $this->logger?->error("Error handling relay message", ['error' => $e->getMessage()]);
            if ($this->verbose) {
                echo "Error handling relay message: " . $e->getMessage() . PHP_EOL;
            }
        }
    }

    /**
     * Process notification event and trigger appropriate callback.
     *
     * @param \stdClass $event The notification event
     */
    private function processNotificationEvent(\stdClass $event): void
    {
        try {
            // Get notification type from tags
            $notificationType = null;
            if (isset($event->tags)) {
                foreach ($event->tags as $tag) {
                    if (is_array($tag) && count($tag) >= 2 && $tag[0] === 'notification_type') {
                        $notificationType = $tag[1];
                        break;
                    }
                }
            }

            $this->logger?->debug("Processing notification event", [
                'event_id' => $event->id,
                'notification_type' => $notificationType,
            ]);

            if ($this->verbose) {
                echo "  - Notification type: " . ($notificationType ?? 'unknown') . PHP_EOL;
            }

            // Decrypt notification content
            $clientPrivkey = $this->client->getNwcUri()->getSecret();
            $walletPubkey = $this->client->getNwcUri()->getWalletPubkey();

            $decryptedContent = Nip04::decrypt($event->content, $clientPrivkey, $walletPubkey);
            $notificationData = json_decode($decryptedContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger?->error("Failed to parse decrypted notification", [
                    'error' => json_last_error_msg(),
                ]);
                if ($this->verbose) {
                    echo "  ✗ Failed to parse decrypted notification: " . json_last_error_msg() . PHP_EOL;
                }
                return;
            }

            $this->logger?->info("Notification decrypted successfully", [
                'event_id' => $event->id,
                'notification_type' => $notificationData['notification_type'] ?? 'unknown',
            ]);

            if ($this->verbose) {
                echo "  ✓ Notification decrypted successfully" . PHP_EOL;
                echo "  - Content: " . json_encode($notificationData, JSON_PRETTY_PRINT) . PHP_EOL;
            }

            // Create notification object and trigger callback
            try {
                $notification = NotificationFactory::fromArray($notificationData);

                if ($notification instanceof PaymentReceivedNotification) {
                    $this->logger?->info("Payment received notification", [
                        'amount_msats' => $notification->getAmount(),
                        'amount_sats' => $notification->getAmountInSats(),
                        'payment_hash' => $notification->getPaymentHash(),
                        'description' => $notification->getDescription(),
                    ]);

                    if ($this->verbose) {
                        echo "  📥 PAYMENT RECEIVED!" . PHP_EOL;
                        echo "    - Amount: " . $notification->getAmount() . " msats (" . $notification->getAmountInSats() . " sats)" . PHP_EOL;
                        echo "    - Payment Hash: " . $notification->getPaymentHash() . PHP_EOL;
                        if ($notification->getDescription()) {
                            echo "    - Description: " . $notification->getDescription() . PHP_EOL;
                        }
                    }

                    if ($this->onPaymentReceived) {
                        ($this->onPaymentReceived)($notification, $event);
                    }

                } elseif ($notification instanceof PaymentSentNotification) {
                    $this->logger?->info("Payment sent notification", [
                        'amount_msats' => $notification->getAmount(),
                        'amount_sats' => $notification->getAmountInSats(),
                        'fees_msats' => $notification->getFeesPaid(),
                        'fees_sats' => $notification->getFeesPaidInSats(),
                        'payment_hash' => $notification->getPaymentHash(),
                        'description' => $notification->getDescription(),
                    ]);

                    if ($this->verbose) {
                        echo "  📤 PAYMENT SENT!" . PHP_EOL;
                        echo "    - Amount: " . $notification->getAmount() . " msats (" . $notification->getAmountInSats() . " sats)" . PHP_EOL;
                        echo "    - Fees: " . $notification->getFeesPaid() . " msats (" . $notification->getFeesPaidInSats() . " sats)" . PHP_EOL;
                        echo "    - Payment Hash: " . $notification->getPaymentHash() . PHP_EOL;
                        if ($notification->getDescription()) {
                            echo "    - Description: " . $notification->getDescription() . PHP_EOL;
                        }
                    }

                    if ($this->onPaymentSent) {
                        ($this->onPaymentSent)($notification, $event);
                    }
                }

            } catch (\Exception $e) {
                $this->logger?->error("Failed to create notification object", [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                ]);
                if ($this->verbose) {
                    echo "  ✗ Failed to create notification object: " . $e->getMessage() . PHP_EOL;
                }
            }

        } catch (\Exception $e) {
            $this->logger?->error("Failed to decrypt notification", [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
            ]);
            if ($this->verbose) {
                echo "  ✗ Failed to decrypt notification: " . $e->getMessage() . PHP_EOL;
            }
        }

        if ($this->verbose) {
            echo str_repeat("-", 50) . PHP_EOL;
        }
    }
}
