<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;
use dsbaars\nostr\Nip47\Command\CommandInterface;
use dsbaars\nostr\Nip47\Response\ResponseInterface;
use dsbaars\nostr\Nip47\Response\PayInvoiceResponse;
use dsbaars\nostr\Nip47\Response\GetBalanceResponse;
use dsbaars\nostr\Nip47\Response\GetInfoResponse;
use dsbaars\nostr\Nip47\Response\MakeInvoiceResponse;
use dsbaars\nostr\Nip47\Response\ListTransactionsResponse;
use dsbaars\nostr\Nip47\Response\PayKeysendResponse;
use dsbaars\nostr\Nip47\Response\MultiPayInvoiceResponse;
use dsbaars\nostr\Nip47\Response\MultiPayKeysendResponse;
use dsbaars\nostr\Nip47\Event\RequestEvent;
use dsbaars\nostr\Nip47\Event\ResponseEvent;
use dsbaars\nostr\Nip47\Event\InfoEvent;
use dsbaars\nostr\Nip47\Event\NotificationEvent;
use dsbaars\nostr\Nip47\Exception\CommandException;
use dsbaars\nostr\Nip47\Exception\NwcException;
use swentel\nostr\Key\Key;
use swentel\nostr\Sign\Sign;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Request\Request;
use swentel\nostr\Request\PersistentConnection;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\RelayResponse\RelayResponse;
use Psr\Log\AbstractLogger;

/**
 * Nostr Wallet Connect Client.
 *
 * Handles client-side interactions with NWC wallet services.
 */
class NwcClient
{
    private NwcUri $nwcUri;
    private Key $keyService;
    private Sign $signService;
    private RelaySet $relaySet;
    private string $clientPrivkey;
    private string $clientPubkey;
    private ?AbstractLogger $logger;
    private string $encryptionMethod = 'nip04'; // Default encryption method

    /**
     * Create a new NWC client.
     *
     * @param string|NwcUri $nwcUri NWC connection URI or parsed URI object
     * @param AbstractLogger|null $logger Optional logger for debug output
     * @throws \dsbaars\nostr\Nip47\Exception\InvalidUriException
     */
    public function __construct(string|NwcUri $nwcUri, ?AbstractLogger $logger = null)
    {
        $this->keyService = new Key();
        $this->signService = new Sign();
        $this->logger = $logger;

        // Parse URI if string provided
        if (is_string($nwcUri)) {
            $this->nwcUri = new NwcUri($nwcUri);
        } else {
            $this->nwcUri = $nwcUri;
        }

        // Setup client keys from URI secret
        $this->clientPrivkey = $this->nwcUri->getSecret();
        $this->clientPubkey = $this->keyService->getPublicKey($this->clientPrivkey);

        // Setup relay set
        $this->setupRelays();
    }

    /**
     * Setup relays from NWC URI.
     */
    private function setupRelays(): void
    {
        $this->relaySet = new RelaySet();
        $relays = [];

        foreach ($this->nwcUri->getRelays() as $relayUrl) {
          // Check if we need to decode the relay URL.
          $relayURLIsEncoded = urlencode(urldecode($relayUrl)) === $relayUrl;
          if ($relayURLIsEncoded) {
            $relayUrl = urldecode($relayUrl);
          }
          $relays[] = new Relay($relayUrl);
        }

        $this->relaySet->setRelays($relays);
    }

    /**
     * Get wallet info and capabilities.
     *
     * @return GetInfoResponse
     * @throws NwcException
     */
    public function getWalletInfo(): GetInfoResponse
    {
        // First try to get the info event (kind 13194)
        try {
            $infoEvent = $this->fetchInfoEvent();
            if ($infoEvent) {
                // Convert info event to GetInfoResponse format
                $result = [
                    'methods' => $infoEvent->getSupportedMethods(),
                    'notifications' => $infoEvent->getSupportedNotifications(),
                    'encryption' => $infoEvent->getSupportedEncryptions(),
                ];

                return new GetInfoResponse('get_info', $result);
            }
        } catch (\Exception $e) {
            // If info event fetch fails, continue to try get_info command
        }

        // Fallback to get_info command
        $command = new Command\GetInfoCommand();
        return $this->executeCommand($command, GetInfoResponse::class);
    }

    /**
     * Fetch the info event from relays.
     *
     * @return InfoEvent|null
     */
    private function fetchInfoEvent(): ?InfoEvent
    {
        $filter = new Filter();
        $filter->setKinds([InfoEvent::KIND]);
        $filter->setAuthors([$this->nwcUri->getWalletPubkey()]);
        $filter->setLimit(1);

        $subscription = new Subscription();
        $requestMessage = new RequestMessage($subscription->getId(), [$filter]);
        $request = new Request($this->relaySet, $requestMessage);

        try {
            $results = $request->send();

            foreach ($results as $relayUrl => $responses) {
                if (is_array($responses)) {
                    foreach ($responses as $response) {
                        if (isset($response->event) && $response->event->kind === InfoEvent::KIND) {
                            $infoEvent = new InfoEvent();
                            $infoEvent->setId($response->event->id);
                            $infoEvent->setPublicKey($response->event->pubkey);
                            $infoEvent->setCreatedAt($response->event->created_at ?? 0);
                            $infoEvent->setKind($response->event->kind);
                            $infoEvent->setContent($response->event->content);
                            $infoEvent->setTags($response->event->tags ?? []);
                            $infoEvent->setSignature($response->event->sig ?? '');

                            return $infoEvent;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Return null if fetch fails
        }

        return null;
    }

    /**
     * Get wallet balance.
     *
     * @return GetBalanceResponse
     * @throws NwcException
     */
    public function getBalance(): GetBalanceResponse
    {
        $command = new Command\GetBalanceCommand();
        return $this->executeCommand($command, GetBalanceResponse::class);
    }

    /**
     * Pay a Lightning invoice.
     *
     * @param string $invoice The bolt11 invoice to pay
     * @param int|null $amount Optional amount in millisatoshis for zero-amount invoices
     * @return PayInvoiceResponse
     * @throws NwcException
     */
    public function payInvoice(string $invoice, ?int $amount = null): PayInvoiceResponse
    {
        $command = new Command\PayInvoiceCommand($invoice, $amount);
        return $this->executeCommand($command, PayInvoiceResponse::class);
    }

    /**
     * Create a Lightning invoice.
     *
     * @param int $amount Amount in millisatoshis
     * @param string|null $description Invoice description
     * @param string|null $descriptionHash Invoice description hash
     * @param int|null $expiry Expiry in seconds from creation
     * @return MakeInvoiceResponse
     * @throws NwcException
     */
    public function makeInvoice(int $amount, ?string $description = null, ?string $descriptionHash = null, ?int $expiry = null): MakeInvoiceResponse
    {
        $command = new Command\MakeInvoiceCommand($amount, $description, $descriptionHash, $expiry);
        return $this->executeCommand($command, MakeInvoiceResponse::class);
    }

    /**
     * Lookup an invoice by payment hash or invoice string.
     *
     * @param string|null $paymentHash Payment hash of the invoice
     * @param string|null $invoice Invoice string to lookup
     * @return \dsbaars\nostr\Nip47\Response\LookupInvoiceResponse
     * @throws NwcException
     */
    public function lookupInvoice(?string $paymentHash = null, ?string $invoice = null): Response\LookupInvoiceResponse
    {
        $command = new Command\LookupInvoiceCommand($paymentHash, $invoice);
        return $this->executeCommand($command, Response\LookupInvoiceResponse::class);
    }

    /**
     * List transactions.
     *
     * @param int|null $from Unix timestamp to filter transactions from (inclusive)
     * @param int|null $until Unix timestamp to filter transactions until (inclusive)
     * @param int|null $limit Maximum number of transactions to return
     * @param int|null $offset Number of transactions to skip
     * @param bool|null $unpaid If true, only return unpaid transactions
     * @param string|null $type Filter by transaction type ("incoming" or "outgoing")
     * @return ListTransactionsResponse
     * @throws NwcException
     */
    public function listTransactions(
        ?int $from = null,
        ?int $until = null,
        ?int $limit = null,
        ?int $offset = null,
        ?bool $unpaid = null,
        ?string $type = null,
    ): ListTransactionsResponse {
        $command = new Command\ListTransactionsCommand($from, $until, $limit, $offset, $unpaid, $type);
        return $this->executeCommand($command, ListTransactionsResponse::class);
    }

    /**
     * Pay multiple Lightning invoices.
     *
     * @param array $invoices Array of invoice data, each containing 'invoice' and optionally 'amount'
     * @return MultiPayInvoiceResponse
     * @throws NwcException
     */
    public function multiPayInvoice(array $invoices): MultiPayInvoiceResponse
    {
        $command = new Command\MultiPayInvoiceCommand($invoices);
        return $this->executeCommand($command, MultiPayInvoiceResponse::class);
    }

    /**
     * Send a keysend payment.
     *
     * @param string $destination The destination pubkey (hex)
     * @param int $amount Amount in millisatoshis
     * @param string|null $preimage Optional preimage (hex), if not provided, a random one will be generated
     * @param array $tlvRecords Optional TLV records as key-value pairs
     * @return PayKeysendResponse
     * @throws NwcException
     */
    public function payKeysend(
        string $destination,
        int $amount,
        ?string $preimage = null,
        array $tlvRecords = [],
    ): PayKeysendResponse {
        $command = new Command\PayKeysendCommand($destination, $amount, $preimage, $tlvRecords);
        return $this->executeCommand($command, PayKeysendResponse::class);
    }

    /**
     * Send multiple keysend payments.
     *
     * @param array $keysends Array of keysend data, each containing 'destination', 'amount', and optionally 'preimage' and 'tlv_records'
     * @return MultiPayKeysendResponse
     * @throws NwcException
     */
    public function multiPayKeysend(array $keysends): MultiPayKeysendResponse
    {
        $command = new Command\MultiPayKeysendCommand($keysends);
        return $this->executeCommand($command, MultiPayKeysendResponse::class);
    }

    /**
     * Execute a command and return the response.
     *
     * @param CommandInterface $command The command to execute
     * @param string $responseClass The expected response class
     * @param bool $throwOnError Whether to throw an exception if the response contains an error
     * @return ResponseInterface
     * @throws NwcException
     */
    public function executeCommand(CommandInterface $command, string $responseClass, bool $throwOnError = false): ResponseInterface
    {
        // Validate command
        $command->validate();

        // Create request event
        $requestEvent = new RequestEvent($command, $this->clientPrivkey, $this->nwcUri->getWalletPubkey(), null, $this->encryptionMethod);

        // Sign the event
        $this->signService->signEvent($requestEvent, $this->clientPrivkey);

        $timeoutSeconds = 10;
        $startTime = time();

        // Setup subscription filter for responses
        $responseFilter = new Filter();
        $responseFilter->setKinds([ResponseEvent::KIND]);
        $responseFilter->setAuthors([$this->nwcUri->getWalletPubkey()]);
        $responseFilter->setLowercasePTags([$this->clientPubkey]);
        $responseFilter->setSince($startTime - 5); // Look back slightly to catch the response

        $subscription = new Subscription();
        $subscriptionMessage = new RequestMessage($subscription->getId(), [$responseFilter]);

        $this->logger?->debug("NWC: Starting WebSocket execution with Guzzle", [
            'event_kind' => $requestEvent->getKind(),
            'event_id' => $requestEvent->getId(),
            'command_method' => $command->getMethod(),
            'start_time' => $startTime,
            'timeout' => $timeoutSeconds,
        ]);

        $this->logger?->debug("NWC: Created subscription for responses", [
            'subscription_id' => $subscription->getId(),
            'filter_since' => $startTime - 5,
        ]);

        try {
            // Use the first relay for now (could be enhanced to try multiple relays)
            $relays = $this->relaySet->getRelays();
            if (empty($relays)) {
                throw new CommandException("No relays available");
            }

            $relay = $relays[0];

            $response = $this->executeWithGuzzleWebSocket($relay, $subscriptionMessage, $requestEvent, $responseClass, $startTime, $timeoutSeconds, $command);

            // Optionally throw on error
            if ($throwOnError && $response->isError()) {
                $response->throwIfError();
            }

            return $response;

        } catch (\Exception $e) {
            throw new CommandException("Failed to execute command: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute NWC command using Guzzle WebSocket middleware for real-time callback-based communication.
     *
     * @param Relay $relay
     * @param RequestMessage $subscriptionMessage
     * @param RequestEvent $requestEvent
     * @param string $responseClass
     * @param int $startTime
     * @param int $timeoutSeconds
     * @return ResponseInterface
     * @throws CommandException
     */
    private function executeWithGuzzleWebSocket(Relay $relay, RequestMessage $subscriptionMessage, RequestEvent $requestEvent, string $responseClass, int $startTime, int $timeoutSeconds, CommandInterface $command): ResponseInterface
    {
        $this->logger?->debug("NWC: Setting up Guzzle WebSocket client", [
            'relay' => $relay->getUrl(),
        ]);

        // Set up Guzzle client with WebSocket middleware
        $handlerStack = new HandlerStack(new StreamHandler());
        $handlerStack->unshift(new WebSocketMiddleware());
        $guzzle = new Client(['handler' => $handlerStack]);

        $this->logger?->debug("NWC: Connecting to WebSocket", [
            'relay' => $relay->getUrl(),
        ]);

        // Connect to WebSocket
        $handshakeResponse = $guzzle->requestAsync('GET', $relay->getUrl())->wait();

        if ($handshakeResponse->getStatusCode() !== 101) {
            throw new CommandException("WebSocket handshake failed: " . $handshakeResponse->getReasonPhrase());
        }

        /** @var \Valtzu\WebSocketMiddleware\WebSocketStream $ws */
        $ws = $handshakeResponse->getBody();

        $this->logger?->debug("NWC: WebSocket connected, sending subscription", [
            'subscription_payload' => $subscriptionMessage->generate(),
        ]);

        // FIRST: Send subscription to start listening for responses
        $subscriptionPayload = $subscriptionMessage->generate();
        $ws->write($subscriptionPayload);

        $this->logger?->debug("NWC: Subscription sent, now sending command", [
            'command_payload' => (new EventMessage($requestEvent))->generate(),
        ]);

        // SECOND: Send the command after subscription is established
        $commandPayload = (new EventMessage($requestEvent))->generate();
        $ws->write($commandPayload);

        $this->logger?->debug("NWC: Command sent, now listening for responses");

        // THIRD: Listen for responses with message buffering for fragmented messages
        // Check if this is a multi-command that expects multiple response events
        $isMultiCommand = in_array($command->getMethod(), ['multi_pay_invoice', 'multi_pay_keysend']);
        $expectedResponseCount = 1;

        if ($isMultiCommand) {
            if ($command->getMethod() === 'multi_pay_invoice') {
                /** @var \dsbaars\nostr\Nip47\Command\MultiPayInvoiceCommand $command */
                $expectedResponseCount = count($command->getInvoices());
            } elseif ($command->getMethod() === 'multi_pay_keysend') {
                /** @var \dsbaars\nostr\Nip47\Command\MultiPayKeysendCommand $command */
                $expectedResponseCount = count($command->getKeysends());
            }

            $this->logger?->debug("NWC: Multi-command detected", [
                'command' => $command->getMethod(),
                'expected_responses' => $expectedResponseCount,
            ]);
        }

        $collectedResponses = [];
        $readAttempts = 0;
        $maxReadAttempts = $timeoutSeconds * 10; // 100ms intervals
        $messageBuffer = ''; // Buffer for fragmented messages
        $endTime = time() + $timeoutSeconds;

        while (count($collectedResponses) < $expectedResponseCount && $readAttempts < $maxReadAttempts && time() < $endTime) {
            $wsContent = $ws->read();
            $readAttempts++;

            if ($wsContent !== '') {
                $this->logger?->debug("NWC: Received WebSocket fragment", [
                    'content_length' => strlen($wsContent),
                    'attempt' => $readAttempts,
                    'buffer_length' => strlen($messageBuffer),
                ]);

                // Add to buffer
                $messageBuffer .= $wsContent;

                // Try to parse complete JSON messages from buffer
                $newResponses = $this->processMessageBufferMulti($messageBuffer, $responseClass, $startTime, $requestEvent, $command);
                $collectedResponses = array_merge($collectedResponses, $newResponses);

                $this->logger?->debug("NWC: Response collection progress", [
                    'collected' => count($collectedResponses),
                    'expected' => $expectedResponseCount,
                ]);

                // For single commands, return immediately when we get the response
                if (!$isMultiCommand && count($collectedResponses) > 0) {
                    return $collectedResponses[0];
                }
            } else {
                // No content received, wait a bit before next read
                usleep(100000); // 100ms
            }
        }

        if (count($collectedResponses) === 0) {
            throw new CommandException("No response received within timeout period");
        }

        if ($isMultiCommand && count($collectedResponses) < $expectedResponseCount) {
            $this->logger?->debug("NWC: Incomplete multi-command response", [
                'collected' => count($collectedResponses),
                'expected' => $expectedResponseCount,
            ]);
            // For multi-commands, combine partial responses
            return $this->combineMultiResponses($collectedResponses, $responseClass, $command);
        }

        if ($isMultiCommand) {
            // Combine all responses into a single multi-response
            return $this->combineMultiResponses($collectedResponses, $responseClass, $command);
        }

        return $collectedResponses[0];
    }

    /**
     * Process message buffer to extract complete JSON messages and collect multiple responses.
     *
     * @param string $messageBuffer
     * @param string $responseClass
     * @param int $startTime
     * @param RequestEvent $requestEvent
     * @param CommandInterface $command
     * @return ResponseInterface[]
     */
    private function processMessageBufferMulti(string &$messageBuffer, string $responseClass, int $startTime, RequestEvent $requestEvent, CommandInterface $command): array
    {
        $responses = [];

        // Try to extract complete JSON messages from the buffer
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

                        $this->logger?->debug("NWC: Extracted complete JSON message", [
                            'content_length' => strlen($jsonMessage),
                            'start_pos' => $messageStart,
                            'end_pos' => $i,
                        ]);

                        try {
                            $messageData = json_decode($jsonMessage, false); // Use false to get stdClass objects
                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Parse as relay response
                                $relayResponse = RelayResponse::create($messageData);

                                // Check if this is an event message with our response
                                if ($relayResponse->type === 'EVENT' && isset($relayResponse->event)) {
                                    if ($relayResponse->event->kind === ResponseEvent::KIND) {

                                        $this->logger?->debug("NWC: Processing response event", [
                                            'event_id' => $relayResponse->event->id,
                                            'created_at' => $relayResponse->event->created_at,
                                        ]);

                                        try {

                                            // Determine encryption method from response event tags
                                            $responseEncryptionMethod = $this->encryptionMethod; // default
                                            if (isset($relayResponse->event->tags)) {
                                                foreach ($relayResponse->event->tags as $tag) {
                                                    if (is_array($tag) && count($tag) >= 2 && $tag[0] === 'encryption') {
                                                        $responseEncryptionMethod = $tag[1];
                                                        break;
                                                    }
                                                }
                                            }

                                            // Decrypt and parse response using the detected encryption method
                                            if ($responseEncryptionMethod === 'nip44_v2') {
                                                $conversationKey = \swentel\nostr\Encryption\Nip44::getConversationKey($this->clientPrivkey, $this->nwcUri->getWalletPubkey());

                                                $decryptedContent = \swentel\nostr\Encryption\Nip44::decrypt(
                                                    $relayResponse->event->content,
                                                    $conversationKey,
                                                );
                                            } else {
                                                $decryptedContent = \swentel\nostr\Encryption\Nip04::decrypt(
                                                    $relayResponse->event->content,
                                                    $this->clientPrivkey,
                                                    $this->nwcUri->getWalletPubkey(),
                                                );
                                            }
                                            $decryptedData = json_decode($decryptedContent, true);

                                            // Get expected result type from the original command
                                            $expectedResultType = $command->getMethod();
                                            $responseResultType = $decryptedData['result_type'] ?? '';

                                            // Check if response event references our request event in 'e' tag
                                            $referencesRequestEvent = false;
                                            if (isset($relayResponse->event->tags)) {
                                                foreach ($relayResponse->event->tags as $tag) {
                                                    if (is_array($tag) && count($tag) >= 2 && $tag[0] === 'e' && $tag[1] === $requestEvent->getId()) {
                                                        $referencesRequestEvent = true;
                                                        break;
                                                    }
                                                }
                                            }

                                            // Verify this is a response to our command by checking:
                                            // 1. Timestamp (response created after our request)
                                            // 2. Result type matches the command method
                                            // 3. Response event references our request event in 'e' tag
                                            $timestampMatch = $relayResponse->event->created_at >= $startTime;
                                            $typeMatch = $responseResultType === $expectedResultType;

                                            if ($timestampMatch && $typeMatch && $referencesRequestEvent) {

                                                $this->logger?->debug("NWC: Response found and matched", [
                                                    'event_id' => $relayResponse->event->id,
                                                    'created_at' => $relayResponse->event->created_at,
                                                    'response_type' => $responseClass,
                                                    'expected_result_type' => $expectedResultType,
                                                    'actual_result_type' => $responseResultType,
                                                    'references_request' => $referencesRequestEvent,
                                                    'request_event_id' => $requestEvent->getId(),
                                                    'event_content' => $decryptedData,
                                                ]);

                                                // Create and add response object to collection
                                                $responses[] = $responseClass::fromArray($decryptedData);
                                            } else {
                                                $this->logger?->debug("NWC: Response verification failed, skipping", [
                                                    'event_id' => $relayResponse->event->id,
                                                    'expected_result_type' => $expectedResultType,
                                                    'actual_result_type' => $responseResultType,
                                                    'timestamp_match' => $timestampMatch,
                                                    'type_match' => $typeMatch,
                                                    'references_request' => $referencesRequestEvent,
                                                    'request_event_id' => $requestEvent->getId(),
                                                ]);
                                            }
                                        } catch (\Exception $e) {
                                            $this->logger?->debug("NWC: Failed to decrypt response", [
                                                'error' => $e->getMessage(),
                                                'event_id' => $relayResponse->event->id ?? 'unknown',
                                                'content' => $relayResponse->event,
                                            ]);
                                            // Continue processing other messages
                                        }
                                    }
                                } else {
                                    $this->logger?->debug("NWC: Received non-event message", [
                                        'type' => $relayResponse->type ?? 'unknown',
                                    ]);
                                }

                                // Move to next message
                                $messageStart = $i + 1;

                                // Remove processed part from buffer
                                $messageBuffer = substr($messageBuffer, $i + 1);
                                $bufferLength = strlen($messageBuffer);
                                $i = -1; // Reset counter as we modified the buffer
                            } else {
                                // Invalid JSON, keep trying
                                break;
                            }
                        } catch (\Exception $e) {
                            // Error parsing JSON or creating RelayResponse, skip this message
                            $this->logger?->debug("NWC: Error processing message", [
                                'error' => $e->getMessage(),
                                'json_content' => substr($jsonMessage, 0, 200),
                            ]);

                            // Move to next message
                            $messageStart = $i + 1;
                            $messageBuffer = substr($messageBuffer, $i + 1);
                            $bufferLength = strlen($messageBuffer);
                            $i = -1;
                        }
                    }
                }
            }
        }

        return $responses;
    }

    /**
     * Combine multiple individual responses into a single multi-response object
     *
     * @param ResponseInterface[] $responses
     * @param string $responseClass
     * @param CommandInterface $command
     * @return ResponseInterface
     */
    private function combineMultiResponses(array $responses, string $responseClass, CommandInterface $command): ResponseInterface
    {
        if ($command->getMethod() === 'multi_pay_invoice') {
            // For multi_pay_invoice, combine all individual responses into one MultiPayInvoiceResponse
            $combinedResults = [];

            foreach ($responses as $response) {
                if ($response instanceof \dsbaars\nostr\Nip47\Response\MultiPayInvoiceResponse) {
                    if ($response->isError()) {
                        // This individual payment failed
                        $combinedResults[] = [
                            'preimage' => null,
                            'error' => [
                                'code' => $response->getErrorCode(),
                                'message' => $response->getErrorMessage(),
                            ],
                        ];
                    } else {
                        // This individual payment succeeded
                        // Note: Individual responses might not have all the fields we expect
                        $result = $response->getResult();
                        $combinedResults[] = [
                            'preimage' => $result['preimage'] ?? null,
                            'payment_hash' => $result['payment_hash'] ?? null,
                            'amount' => $result['amount'] ?? null,
                            'fees_paid' => $result['fees_paid'] ?? null,
                        ];
                    }
                }
            }

            return new \dsbaars\nostr\Nip47\Response\MultiPayInvoiceResponse(
                resultType: 'multi_pay_invoice',
                result: ['payments' => $combinedResults],
                error: null,
            );

        } elseif ($command->getMethod() === 'multi_pay_keysend') {
            // For multi_pay_keysend, combine all individual responses into one MultiPayKeysendResponse
            $combinedResults = [];

            foreach ($responses as $response) {
                if ($response instanceof \dsbaars\nostr\Nip47\Response\MultiPayKeysendResponse) {
                    if ($response->isError()) {
                        // This individual payment failed
                        $combinedResults[] = [
                            'preimage' => null,
                            'error' => [
                                'code' => $response->getErrorCode(),
                                'message' => $response->getErrorMessage(),
                            ],
                        ];
                    } else {
                        // This individual payment succeeded
                        $result = $response->getResult();
                        $combinedResults[] = [
                            'preimage' => $result['preimage'] ?? null,
                            'payment_hash' => $result['payment_hash'] ?? null,
                            'amount' => $result['amount'] ?? null,
                            'fees_paid' => $result['fees_paid'] ?? null,
                        ];
                    }
                }
            }

            return new \dsbaars\nostr\Nip47\Response\MultiPayKeysendResponse(
                resultType: 'multi_pay_keysend',
                result: ['payments' => $combinedResults],
                error: null,
            );
        }

        // Fallback: return the first response if we can't combine
        return $responses[0] ?? throw new CommandException("No responses to combine");
    }

    /**
     * Get the NWC URI.
     *
     * @return NwcUri
     */
    public function getNwcUri(): NwcUri
    {
        return $this->nwcUri;
    }

    /**
     * Get the client public key.
     *
     * @return string
     */
    public function getClientPubkey(): string
    {
        return $this->clientPubkey;
    }

    /**
     * Get the wallet service public key.
     *
     * @return string
     */
    public function getWalletPubkey(): string
    {
        return $this->nwcUri->getWalletPubkey();
    }

    /**
     * Set the encryption method to use for communication.
     *
     * @param string $method The encryption method ('nip04' or 'nip44_v2')
     * @throws \InvalidArgumentException If the encryption method is not supported
     */
    public function setEncryption(string $method): void
    {
        if (!in_array($method, ['nip04', 'nip44_v2'])) {
            throw new \InvalidArgumentException("Unsupported encryption method: {$method}. Supported methods are: nip04, nip44_v2");
        }
        $this->encryptionMethod = $method;
    }

    /**
     * Get the current encryption method.
     *
     * @return string
     */
    public function getEncryptionMethod(): string
    {
        return $this->encryptionMethod;
    }

    /**
     * Get a filter for notifications.
     *
     * @return Filter
     */
    public function getNotificationFilter(): Filter
    {
        $filter = new Filter();
        $filter->setKinds([NotificationEvent::KIND]);
        $filter->setAuthors([$this->nwcUri->getWalletPubkey()]);
        $filter->setLowercasePTags([$this->clientPubkey]);
        $filter->setLimit(1);
        return $filter;
    }
}
