<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

use Psr\SimpleCache\CacheException;

class PortalStorageNormalizationException extends \Exception implements CacheException
{
    private string $key;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(string $key, $value, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('The value in key %s is not of a supported type', $key), 1631377506, $previous);
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
