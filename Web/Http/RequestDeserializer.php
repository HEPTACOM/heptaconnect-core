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
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    public function deserialize(string $requestData): RequestInterface
    {
        try {
            $requestPayload = (array) \json_decode(
                $requestData,
                true,
                512,
                \JSON_INVALID_UTF8_IGNORE | \JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $jsonError) {
            throw new RequestDeserializationException($requestData, 1666451009, $jsonError);
        }

        $method = $requestPayload['method'] ?? null;

        if (!\is_string($method)) {
            throw new RequestDeserializationException($requestData, 1666451010, new \InvalidArgumentException('$.method is not a string'));
        }

        $uri = $requestPayload['uri'] ?? null;

        if (!\is_string($uri)) {
            throw new RequestDeserializationException($requestData, 1666451011, new \InvalidArgumentException('$.uri is not a string'));
        }

        $requestTarget = $requestPayload['requestTarget'] ?? null;
        $protocolVersion = $requestPayload['protocolVersion'] ?? null;
        $content = $requestPayload['body'] ?? null;
        $headers = $requestPayload['headers'] ?? null;

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
                try {
                    $request = $request->withHeader((string) $name, $this->castToScalarArray((string) $name, $values));
                } catch (\Throwable $throwable) {
                    throw new RequestDeserializationException($requestData, 1666451012, $throwable);
                }
            }
        }

        return $request;
    }

    /**
     * @return string[]|string
     */
    private function castToScalarArray(string $headerName, mixed $value): array|string
    {
        if (\is_scalar($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $result = [];

            foreach ($value as $index => $item) {
                if (!\is_scalar($item)) {
                    throw new \InvalidArgumentException(\sprintf('$.headers[%s][%s] is neither a string nor a list', $headerName, $index));
                }

                $result[$index] = (string) $item;
            }

            return $result;
        }

        throw new \InvalidArgumentException(\sprintf('$.headers[%s] is neither a string nor a list', $headerName));
    }
}
