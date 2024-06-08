<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final readonly class StreamDenormalizer implements DenormalizerInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        private string $streamDataDirectory,
        private StreamPathContract $streamPath
    ) {
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @phpstan-return 'stream'
     */
    #[\Override]
    public function getType(): string
    {
        return 'stream';
    }

    #[\Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SerializableStream
    {
        if (!\is_string($data)) {
            throw new UnexpectedValueException('data is null', 1634868818);
        }

        if ($data === '') {
            throw new UnexpectedValueException('data is empty', 1634868819);
        }

        $resource = \fopen($this->getFullPath($data), 'rb');

        if ($resource === false) {
            throw new UnexpectedValueException('File can not be read from', 1637101289);
        }

        return new SerializableStream($this->streamFactory->createStreamFromResource($resource));
    }

    #[\Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!\is_string($data)) {
            return false;
        }

        if ($data === '') {
            return false;
        }

        if ($type !== $this->getType()) {
            return false;
        }

        return \file_exists($this->getFullPath($data));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }

    private function getFullPath(string $data): string
    {
        return $this->streamDataDirectory . '/' . ltrim($this->streamPath->buildPath($data), '/');
    }
}
