<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;

class StreamNormalizer implements NormalizerInterface
{
    /**
     * @deprecated use \Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract::STORAGE_LOCATION
     */
    public const STORAGE_LOCATION = StreamPathContract::STORAGE_LOCATION;

    public const NS_FILENAME = '048a23d3ac504a67a477da1d098090b0';

    private FilesystemInterface $filesystem;

    private StreamPathContract $streamPath;

    public function __construct(FilesystemInterface $filesystem, StreamPathContract $streamPath)
    {
        $this->filesystem = $filesystem;
        $this->streamPath = $streamPath;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof SerializableStream;
    }

    public function getType(): string
    {
        return 'stream';
    }

    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof SerializableStream) {
            throw new InvalidArgumentException();
        }

        $mediaId = $context['mediaId'] ?? null;

        if ($mediaId === null) {
            $filename = Uuid::uuid4()->getHex();
        } else {
            $filename = Uuid::uuid5(self::NS_FILENAME, $mediaId)->getHex();
        }

        $stream = $object->copy()->detach();
        $this->filesystem->putStream($this->streamPath->buildPath($filename), $stream);

        if (\is_resource($stream)) {
            \fclose($stream);
        }

        return $filename;
    }
}
