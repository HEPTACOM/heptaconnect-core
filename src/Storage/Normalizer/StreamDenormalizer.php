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

        return new SerializableStream($this->streamFactory->createStreamFromResource(
            $this->filesystem->readStream($this->streamPath->buildPath($data))
        ));
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return \is_string($data) && $data !== "" && ($type === $this->getType());
    }
}
