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
        $requestPayload = $this->validateRequestData($requestData);
        $requestTarget = $requestPayload['requestTarget'];
        $protocolVersion = $requestPayload['protocolVersion'];
        $content = $requestPayload['body'];
        $headers = $requestPayload['headers'];

        $request = $this->requestFactory->createRequest($requestPayload['method'], $requestPayload['uri']);

        if (\is_string($requestTarget)) {
            $request = $request->withRequestTarget($requestTarget);
        }

        if (\is_string($protocolVersion)) {
            $request = $request->withProtocolVersion($protocolVersion);
        }

        if (\is_string($content)) {
            $request = $request->withBody($this->streamFactory->createStream($content));
        }

        foreach ($headers as $name => $values) {
            $request = $request->withHeader($name, $values);
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

    private function validateStringValue(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (!\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('$.%s is not a string', $key));
        }

        return $value;
    }

    private function validateOptionalStringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && !\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('$.%s is set but not a string', $key));
        }

        return $value;
    }

    /**
     * @return array{uri: string, method: string, requestTarget: ?string, protocolVersion: ?string, body: ?string, headers: array<string, string|string[]>}
     */
    private function validateRequestData(string $requestData): array
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

        $headers = $requestPayload['headers'] ?? [];
        $goodHeaders = [];

        if (\is_array($headers)) {
            foreach ($headers as $name => $values) {
                try {
                    $goodHeaders[(string) $name] = $this->castToScalarArray((string) $name, $values);
                } catch (\Throwable $throwable) {
                    throw new RequestDeserializationException($requestData, 1666451012, $throwable);
                }
            }
        }

        try {
            return [
                'uri' => $this->validateStringValue($requestPayload, 'uri'),
                'method' => $this->validateStringValue($requestPayload, 'method'),
                'requestTarget' => $this->validateOptionalStringValue($requestPayload, 'requestTarget'),
                'protocolVersion' => $this->validateOptionalStringValue($requestPayload, 'protocolVersion'),
                'body' => $this->validateOptionalStringValue($requestPayload, 'body'),
                'headers' => $goodHeaders,
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new RequestDeserializationException($requestData, 1666451011, $exception);
        }
    }
}
