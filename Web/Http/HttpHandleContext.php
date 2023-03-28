<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class HttpHandleContext extends AbstractPortalNodeContext implements HttpHandleContextInterface
{
    /**
     * @param UriInterface|string $uri
     * @param string $method
     * @param StreamInterface|array|string|null $body
     * @param array<string, string>|array<string, array<array-key, string>> $headers
     *
     * @return ResponseInterface
     */
    public function forward(
        $uri,
        string $method = 'GET',
        $body = null,
        array $headers = []
    ): ResponseInterface {
        /** @var ServerRequestFactoryInterface $requestFactory */
        $requestFactory = $this->getContainer()->get(ServerRequestFactoryInterface::class);
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $this->getContainer()->get(StreamFactoryInterface::class);
        /** @var HttpKernelInterface $httpKernel */
        $httpKernel = $this->getContainer()->get(HttpKernelInterface::class);

        $request = $requestFactory->createServerRequest($method, $uri);

        if (\is_array($body)) {
            $flags = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION;
            $body = \json_encode($body, $flags);
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        if (\is_string($body)) {
            $body = $streamFactory->createStream($body);
        }

        if ($body instanceof StreamInterface) {
            $request = $request->withBody($body);
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $httpKernel->handle($request);
    }
}
