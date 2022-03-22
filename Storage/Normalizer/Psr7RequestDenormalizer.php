<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Psr7RequestDenormalizer implements DenormalizerInterface
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
     * @return RequestInterface
     */
    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        $requestData = \json_decode(
            $data,
            true,
            512,
            \JSON_INVALID_UTF8_IGNORE | \JSON_THROW_ON_ERROR
        );

        $request = $this->requestFactory->createRequest($requestData['method'], $requestData['uri'])
            ->withRequestTarget($requestData['requestTarget'])
            ->withProtocolVersion($requestData['protocolVersion'])
            ->withBody($this->streamFactory->createStream($requestData['body']));

        foreach ($requestData['headers'] as $name => $values) {
            $request = $request->withHeader($name, $values);
        }

        return $request;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null)
    {
        if ($type !== $this->getType() || !\is_string($data)) {
            return false;
        }

        try {
            \json_decode(
                $data,
                true,
                512,
                \JSON_INVALID_UTF8_IGNORE | \JSON_THROW_ON_ERROR
            );

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
