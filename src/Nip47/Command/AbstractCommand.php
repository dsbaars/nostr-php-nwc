<?php

declare(strict_types=1);

namespace dsbaars\nostr\Nip47\Command;

/**
 * Abstract base class for all NWC commands.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Command parameters.
     *
     * @var array
     */
    protected array $params = [];

    /**
     * {@inheritdoc}
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'method' => $this->getMethod(),
            'params' => $this->getParams(),
        ];
    }

    /**
     * Set a parameter value.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    protected function setParam(string $key, mixed $value): static
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Get a parameter value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Check if parameter exists.
     *
     * @param string $key
     * @return bool
     */
    protected function hasParam(string $key): bool
    {
        return isset($this->params[$key]);
    }
}
