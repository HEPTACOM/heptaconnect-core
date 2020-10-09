<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\Struct\SerializableStream;
use League\Flysystem\Filesystem;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class StreamNormalizer implements NormalizerInterface
{
    public const STORAGE_LOCATION = '42c5acf20a7011eba428f754dbb80254';

    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
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

        $filename = Uuid::uuid4()->getHex();
        $stream = $object->copy()->detach();

        $this->filesystem->putStream(self::STORAGE_LOCATION . '/' . $filename, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $filename;
    }
}
