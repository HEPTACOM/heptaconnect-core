<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Http\Discovery\Psr17FactoryDiscovery;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StreamDenormalizer implements DenormalizerInterface
{
    private FilesystemInterface $filesystem;

    private StreamFactoryInterface $streamFactory;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public function getType(): string
    {
        return 'stream';
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return new SerializableStream($this->streamFactory->createStreamFromResource(
            $this->filesystem->readStream(StreamNormalizer::STORAGE_LOCATION.'/'.$data)
        ));
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === $this->getType();
    }
}
