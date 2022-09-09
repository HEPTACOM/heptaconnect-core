<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr7RequestDenormalizer implements DenormalizerInterface
{
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct()
    {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    /**
     * @param string|null $format
     *
     * @return RequestInterface
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $requestData = (array) \json_decode(
            $data,
            true,
            512,
            \JSON_INVALID_UTF8_IGNORE | \JSON_THROW_ON_ERROR
        );

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

    /**
     * @param string|null $format
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type !== $this->getType() || !\is_string($data)) {
            return false;
        }

        try {
            $this->denormalize($data, $type, $format);

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
