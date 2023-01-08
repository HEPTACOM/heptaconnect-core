<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpMiddlewareHandler implements RequestHandlerInterface
{
    /**
     * @var \Closure(ServerRequestInterface): ResponseInterface
     */
    private \Closure $handleStack;

    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares;

    /**
     * @param \Closure(ServerRequestInterface): ResponseInterface $next
     */
    public function __construct(
        \Closure $next,
        MiddlewareInterface ...$middlewares
    ) {
        $this->handleStack = $next;
        $this->middlewares = $middlewares;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->next($request, ...$this->middlewares);
    }

    private function next(ServerRequestInterface $request, MiddlewareInterface ...$middlewares): ResponseInterface
    {
        $middleware = \array_shift($middlewares);

        if ($middleware instanceof MiddlewareInterface) {
            /** @var \Closure(ServerRequestInterface): ResponseInterface $next */
            $next = \Closure::fromCallable(fn (ServerRequestInterface $request) => $this->next($request, ...$middlewares));

            $handler = new class($next) implements RequestHandlerInterface {
                /**
                 * @param \Closure(ServerRequestInterface): ResponseInterface $next
                 */
                public function __construct(
                    private \Closure $next
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return ($this->next)($request);
                }
            };

            return $middleware->process($request, $handler);
        }

        return ($this->handleStack)($request);
    }
}
