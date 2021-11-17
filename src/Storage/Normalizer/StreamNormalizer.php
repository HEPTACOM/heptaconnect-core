<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
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

    private LoggerInterface $logger;

    public function __construct(
        FilesystemInterface $filesystem,
        StreamPathContract $streamPath,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->streamPath = $streamPath;
        $this->logger = $logger;
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return $data instanceof SerializableStream;
    }

    public function getType(): string
    {
        return 'stream';
    }

    /**
     * @return string
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        if (!$object instanceof SerializableStream) {
            throw new InvalidArgumentException();
        }

        $mediaId = $context['mediaId'] ?? null;

        if ($mediaId === null) {
            $filename = (string) Uuid::uuid4()->getHex();
        } else {
            $filename = (string) Uuid::uuid5(self::NS_FILENAME, $mediaId)->getHex();
        }

        $stream = $object->copy()->detach();
        $path = $this->streamPath->buildPath($filename);

        $this->logger->debug(LogMessage::STORAGE_STREAM_NORMALIZER_CONVERTS_HINT_TO_FILENAME(), [
            'filename' => $filename,
            'path' => $path,
            'mediaId' => $mediaId,
            'code' => 1635462690,
        ]);

        $this->filesystem->putStream($path, $stream);

        if (\is_resource($stream)) {
            \fclose($stream);
        }

        return $filename;
    }
}
