<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestDeserializerInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Exception\RequestDeserializationException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class RequestDeserializer implements RequestDeserializerInterface
{
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function deserialize(string $requestData): RequestInterface
    {
        try {
            $requestData = (array) \json_decode(
                $requestData,
                true,
                512,
                \JSON_INVALID_UTF8_IGNORE | \JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $jsonError) {
            throw new RequestDeserializationException($requestData, 1666451009, $jsonError);
        }

        $method = $requestData['method'] ?? null;
        $uri = $requestData['uri'] ?? null;
        $requestTarget = $requestData['requestTarget'] ?? null;
        $protocolVersion = $requestData['protocolVersion'] ?? null;
        $content = $requestData['body'] ?? null;
        $headers = $requestData['headers'] ?? null;

        $request = $this->requestFactory->createRequest($method, $uri);

        if (\is_string($requestTarget)) {
            $request = $request->withRequestTarget($requestTarget);
        }

        if (\is_string($protocolVersion)) {
            $request = $request->withProtocolVersion($protocolVersion);
        }

        if (\is_string($content)) {
            $request = $request->withBody($this->streamFactory->createStream($content));
        }

        if (\is_array($headers)) {
            foreach ($headers as $name => $values) {
                $request = $request->withHeader($name, $values);
            }
        }

        return $request;
    }
}
