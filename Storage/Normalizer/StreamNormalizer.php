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
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;

final class StreamNormalizer implements NormalizerInterface
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

    /**
     * @param string|null $format
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof SerializableStream;
    }

    /**
     * @psalm-return 'stream'
     */
    public function getType(): string
    {
        return 'stream';
    }

    /**
     * @param string|null $format
     *
     * @return string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof SerializableStream) {
            throw new InvalidArgumentException('$object is no SerializableStream', 1637432853);
        }

        $mediaId = $context['mediaId'] ?? null;
        $filenameUuid = $mediaId === null ? Uuid::uuid4() : Uuid::uuid5(self::NS_FILENAME, $mediaId);
        /** @var string|Hexadecimal $generatedFilename */
        $generatedFilename = $filenameUuid->getHex();

        if (\class_exists(Hexadecimal::class) && $generatedFilename instanceof Hexadecimal) {
            $filename = $generatedFilename->toString();
            /** @var string $generatedFilename */
        } else {
            /** @var string $generatedFilename */
            $filename = $generatedFilename;
        }

        $stream = $object->copy()->detach();

        if ($stream === null) {
            throw new InvalidArgumentException('stream is invalid', 1637432854);
        }

        $path = $this->streamPath->buildPath($filename);

        $this->logger->debug(LogMessage::STORAGE_STREAM_NORMALIZER_CONVERTS_HINT_TO_FILENAME(), [
            'filename' => $filename,
            'path' => $path,
            'mediaId' => $mediaId,
            'code' => 1635462690,
        ]);

        $this->filesystem->putStream($path, $stream);

        \fclose($stream);

        return $filename;
    }
}
