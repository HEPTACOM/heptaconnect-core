<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class StreamNormalizer implements NormalizerInterface
{
    /**
     * @deprecated use \Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract::STORAGE_LOCATION
     */
    public const STORAGE_LOCATION = StreamPathContract::STORAGE_LOCATION;

    public const NS_FILENAME = '048a23d3ac504a67a477da1d098090b0';

    public function __construct(
        private string $streamDataDirectory,
        private StreamPathContract $streamPath,
        private LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SerializableStream;
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
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        if (!$object instanceof SerializableStream) {
            throw new InvalidArgumentException('$object is no SerializableStream', 1637432853);
        }

        $mediaId = $context['mediaId'] ?? null;

        if (!\is_string($mediaId)) {
            $mediaId = null;
        }

        $filename = $this->generateFilename(
            $mediaId === null ? Uuid::uuid4() : Uuid::uuid5(self::NS_FILENAME, $mediaId)
        );

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

        $fullPath = $this->streamDataDirectory . \DIRECTORY_SEPARATOR . ltrim($path, '/');
        $fullDir = \dirname($fullPath);

        if (!\is_dir($fullDir)) {
            \mkdir($fullDir, 0777, true);
        }

        $written = @\file_put_contents($fullPath, $stream);

        \fclose($stream);

        if ($written === false) {
            throw new InvalidArgumentException('File could not be written', 1716752000);
        }

        return $filename;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }

    private function generateFilename(UuidInterface $filenameUuid): string
    {
        /** @var string|Hexadecimal $generatedFilename */
        $generatedFilename = $filenameUuid->getHex();

        if (\class_exists(Hexadecimal::class) && $generatedFilename instanceof Hexadecimal) {
            return $generatedFilename->toString();
        }

        return (string) $generatedFilename;
    }
}
