<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

use Throwable;

abstract class AbstractInstantiationException extends \RuntimeException
{
    /**
     * @psalm-var class-string
     */
    private string $class;

    /**
     * @psalm-param class-string $class
     */
    public function __construct(string $class, ?Throwable $previous = null)
    {
        parent::__construct('Could not instantiate object', 0, $previous);

        $this->class = $class;
    }

    /**
     * @psalm-return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
