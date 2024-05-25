<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Http\Discovery\Psr17FactoryDiscovery;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final readonly class StreamDenormalizer implements DenormalizerInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        private FilesystemReader $filesystem,
        private StreamPathContract $streamPath
    ) {
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @psalm-return 'stream'
     */
    public function getType(): string
    {
        return 'stream';
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SerializableStream
    {
        if (!\is_string($data)) {
            throw new UnexpectedValueException('data is null', 1634868818);
        }

        if ($data === '') {
            throw new UnexpectedValueException('data is empty', 1634868819);
        }

        try {
            $resource = $this->filesystem->readStream($this->streamPath->buildPath($data));
        } catch (FilesystemException $e) {
            throw new UnexpectedValueException('File can not be read from', 1637101289, $e);
        }

        return new SerializableStream($this->streamFactory->createStreamFromResource($resource));
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!\is_string($data)) {
            return false;
        }

        if ($data !== '') {
            return false;
        }

        if ($type !== $this->getType()) {
            return false;
        }

        try {
            return $this->filesystem->fileExists($this->streamPath->buildPath($data));
        } catch (FilesystemException) {
            return false;
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }
}
