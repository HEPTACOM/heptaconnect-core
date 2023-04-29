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
    public function forward(
        $uri,
        string $method = 'GET',
        $body = null,
        array $headers = []
    ): ResponseInterface {
        if (!$uri instanceof UriInterface && !\is_string($uri)) {
            throw $this->createTypeError(1, '$uri', '\Psr\Http\Message\UriInterface|string', $uri);
        }

        if (
            !$body instanceof StreamInterface
            && !\is_array($body)
            && !\is_string($body)
            && $body !== null
        ) {
            throw $this->createTypeError(3, '$body', '\Psr\Http\Message\StreamInterface|array|string|null', $body);
        }

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

    private function createTypeError(
        int $position,
        string $name,
        string $expectedType,
        $argument
    ): \TypeError {
        $actualType = \gettype($argument);

        if ($actualType === 'object') {
            $actualType = \get_class($argument);
        }

        return new \TypeError(\sprintf(
            'Argument #%d (%s) must be of type %s, %s given',
            $position,
            $name,
            $expectedType,
            $actualType
        ));
    }
}
