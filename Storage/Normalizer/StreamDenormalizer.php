<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Http\Discovery\Psr17FactoryDiscovery;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class StreamDenormalizer implements DenormalizerInterface
{
    private FilesystemInterface $filesystem;

    private StreamFactoryInterface $streamFactory;

    private StreamPathContract $streamPath;

    public function __construct(FilesystemInterface $filesystem, StreamPathContract $streamPath)
    {
        $this->filesystem = $filesystem;
        $this->streamPath = $streamPath;
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @psalm-return 'stream'
     */
    public function getType(): string
    {
        return 'stream';
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!\is_string($data)) {
            throw new UnexpectedValueException('data is null', 1634868818);
        }

        if ($data === '') {
            throw new UnexpectedValueException('data is empty', 1634868819);
        }

        $resource = $this->filesystem->readStream($this->streamPath->buildPath($data));

        if ($resource === false) {
            throw new UnexpectedValueException('File can not be read from', 1637101289);
        }

        return new SerializableStream($this->streamFactory->createStreamFromResource($resource));
    }

    public function supportsDenormalization($data, $type, $format = null)
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

        if (!$this->filesystem->has($this->streamPath->buildPath($data))) {
            return false;
        }

        return true;
    }
}
