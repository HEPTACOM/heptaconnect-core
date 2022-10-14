<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Support;

use Psr\Http\Server\MiddlewareInterface;

final class HttpMiddlewareCollector implements \IteratorAggregate
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares;

    /**
     * @param iterable<MiddlewareInterface> $middlewares
     */
    public function __construct(iterable $middlewares)
    {
        $this->middlewares = \iterable_to_array($middlewares);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayObject($this->middlewares);
    }
}
