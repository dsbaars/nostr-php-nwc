<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Response;

use dsbaars\nostr\Nip47\ErrorCode;
use dsbaars\nostr\Nip47\Exception\CommandException;

/**
 * Abstract base class for all NWC responses.
 */
abstract class AbstractResponse implements ResponseInterface
{
    /**
     * The result type.
     *
     * @var string
     */
    protected string $resultType;

    /**
     * Error information if any.
     *
     * @var array|null
     */
    protected ?array $error = null;

    /**
     * Result data if successful.
     *
     * @var array|null
     */
    protected ?array $result = null;

    /**
     * Create response from parsed data.
     *
     * @param string $resultType
     * @param array|null $result
     * @param array|null $error
     */
    public function __construct(string $resultType, ?array $result = null, ?array $error = null)
    {
        $this->resultType = $resultType;
        $this->result = $result;
        $this->error = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function getResultType(): string
    {
        return $this->resultType;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return $this->error !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * Get error code if there's an error.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->error['code'] ?? null;
    }

    /**
     * Get error message if there's an error.
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->error['message'] ?? null;
    }

    /**
     * Throw exception if response contains an error.
     *
     * @return void
     * @throws \dsbaars\nostr\Nip47\Exception\NwcException
     */
    public function throwIfError(): void
    {
        if ($this->isError()) {
            $code = $this->getErrorCode();
            $message = $this->getErrorMessage() ?? 'Unknown error';

            if ($code && ErrorCode::tryFrom($code)) {
                throw ErrorCode::from($code)->createException($message);
            }

            throw new CommandException($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data = [
            'result_type' => $this->resultType,
        ];

        if ($this->isError()) {
            $data['error'] = $this->error;
            $data['result'] = null;
        } else {
            $data['error'] = null;
            $data['result'] = $this->result;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): static
    {
        $resultType = $data['result_type'] ?? '';
        $result = $data['result'] ?? null;
        $error = $data['error'] ?? null;

        return new static($resultType, $result, $error);
    }

    /**
     * Get a specific field from the result.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getResultField(string $key, mixed $default = null): mixed
    {
        return $this->result[$key] ?? $default;
    }

    /**
     * Check if a result field exists.
     *
     * @param string $key
     * @return bool
     */
    protected function hasResultField(string $key): bool
    {
        return isset($this->result[$key]);
    }
}
