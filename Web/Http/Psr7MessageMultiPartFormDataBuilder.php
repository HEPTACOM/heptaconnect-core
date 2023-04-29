<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageMultiPartFormDataBuilderInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

final class Psr7MessageMultiPartFormDataBuilder implements Psr7MessageMultiPartFormDataBuilderInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function build(MessageInterface $message, array $parameters): MessageInterface
    {
        \array_walk_recursive($parameters, static function ($parameter, $key): void {
            if (!\is_scalar($parameter) && !$parameter instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'Parameter "%s" is invalid. Parameters must either be scalar or implement %s',
                    $key,
                    UploadedFileInterface::class
                ), 1682806294);
            }
        });

        $boundary = $this->findBoundary($message);
        $boundary ??= '----' . Uuid::uuid4()->toString();

        $parts = $this->flattenMultiPartFormData($parameters, $boundary);
        $body = \implode(\PHP_EOL, $parts) . \PHP_EOL . '--' . $boundary . '--';

        $message = $message->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
        $message = $message->withBody($this->streamFactory->createStream($body));

        return $message;
    }

    private function findBoundary(MessageInterface $message): ?string
    {
        $directives = \explode(';', $message->getHeaderLine('Content-Type'));
        $directives = \array_map('trim', $directives);
        $mediaType = \array_shift($directives);

        if ($mediaType !== 'multipart/form-data') {
            return null;
        }

        foreach ($directives as $directive) {
            if (\str_starts_with($directive, 'boundary=')) {
                $boundary = \mb_substr($directive, \strlen('boundary='));

                return $boundary === '' ? null : $boundary;
            }
        }

        return null;
    }

    private function flattenMultiPartFormData(array $data, string $boundary, ?string $prefix = null): array
    {
        $parts = [];

        foreach ($data as $name => $value) {
            $name = (string) $name;
            $newPrefix = $prefix === null ? $name : $prefix . '[' . $name . ']';

            if (\is_array($value)) {
                $parts = \array_merge(
                    $parts,
                    $this->flattenMultiPartFormData($value, $boundary, $newPrefix)
                );
            } else {
                $partLines = ['--' . $boundary];

                if ($value instanceof UploadedFileInterface) {
                    $partLines[] = \sprintf(
                        'Content-Disposition: form-data; name="%s"; filename*="%s"',
                        $newPrefix,
                        "utf-8''" . \addcslashes(\rawurlencode($value->getClientFilename()), '"\\')
                    );

                    $partLines[] = 'Content-Type: ' . $value->getClientMediaType();
                    $partLines[] = '';
                    $partLines[] = (string) $value->getStream();
                } else {
                    $partLines[] = \sprintf(
                        'Content-Disposition: form-data; name="%s"',
                        $newPrefix
                    );

                    $partLines[] = '';
                    $partLines[] = (string) $value;
                }

                $parts[$newPrefix] = \implode(\PHP_EOL, $partLines);
            }
        }

        return $parts;
    }
}
