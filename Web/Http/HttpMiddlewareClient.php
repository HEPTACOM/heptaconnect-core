<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpMiddlewareClient implements ClientInterface
{
    private ClientInterface $client;

    /**
     * @var HttpClientMiddlewareInterface[]
     */
    private array $middlewares;

    /**
     * @param iterable<HttpClientMiddlewareInterface> $middlewares
     */
    public function __construct(
        ClientInterface $client,
        iterable $middlewares
    ) {
        $this->client = $client;
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
            $next = \Closure::fromCallable(function (RequestInterface $request) use ($middlewares) {
                return $this->next($request, ...$middlewares);
            });

            $handler = new class($next) implements ClientInterface {
                private \Closure $next;

                public function __construct(\Closure $next)
                {
                    $this->next = $next;
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
