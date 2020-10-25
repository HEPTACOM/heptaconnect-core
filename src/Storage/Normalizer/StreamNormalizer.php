<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\Struct\SerializableStream;
use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class StreamNormalizer implements NormalizerInterface
{
    public const STORAGE_LOCATION = '42c5acf20a7011eba428f754dbb80254';

    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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

        $filename = $context['mediaId'] ?? Uuid::uuid4()->getHex();
        $stream = $object->copy()->detach();

        $this->filesystem->putStream(self::STORAGE_LOCATION . '/' . $filename, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $filename;
    }
}
