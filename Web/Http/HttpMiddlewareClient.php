<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpMiddlewareClient implements ClientInterface
{
    /**
     * @var HttpClientMiddlewareInterface[]
     */
    private array $middlewares;

    /**
     * @param iterable<HttpClientMiddlewareInterface> $middlewares
     */
    public function __construct(
        private ClientInterface $client,
        iterable $middlewares
    ) {
        $this->middlewares = \iterable_to_array($middlewares);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->next($request, ...$this->middlewares);
    }

    private function next(RequestInterface $request, HttpClientMiddlewareInterface ...$middlewares): ResponseInterface
    {
        $middleware = \array_shift($middlewares);

        if ($middleware instanceof HttpClientMiddlewareInterface) {
            $next = \Closure::fromCallable(fn (RequestInterface $request) => $this->next($request, ...$middlewares));

            $handler = new class($next) implements ClientInterface {
                /**
                 * @param \Closure(RequestInterface): ResponseInterface $next
                 */
                public function __construct(
                    private \Closure $next
                ) {
                }

                public function sendRequest(RequestInterface $request): ResponseInterface
                {
                    return ($this->next)($request);
                }
            };

            return $middleware->process($request, $handler);
        }

        return $this->client->sendRequest($request);
    }
}
