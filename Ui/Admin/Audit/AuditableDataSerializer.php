<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditableDataSerializerInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachmentAwareInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Audit\AuditableDataAwareInterface;
use Psr\Log\LoggerInterface;

final class AuditableDataSerializer implements AuditableDataSerializerInterface
{
    public function __construct(private LoggerInterface $logger, private StorageKeyGeneratorContract $storageKeyGenerator, private int $jsonEncodeFlags = \JSON_UNESCAPED_SLASHES)
    {
    }

    public function serialize(AuditableDataAwareInterface $auditableDataAware): string
    {
        $result = [
            'data' => $this->extractAuditableData($auditableDataAware),
        ];

        if ($auditableDataAware instanceof AttachmentAwareInterface) {
            $result['attachedTypes'] = $this->extractAttachableData($auditableDataAware);
        }

        return $this->jsonEncode($result);
    }

    private function extractAuditableData(AuditableDataAwareInterface $auditableDataAware): array
    {
        try {
            $result = $auditableDataAware->getAuditableData();

            foreach ($result as &$item) {
                if ($item instanceof StorageKeyInterface) {
                    try {
                        $item = $this->storageKeyGenerator->serialize($item);
                    } catch (UnsupportedStorageKeyException) {
                    }
                }
            }

            return $result;
        } catch (\Throwable $throwable) {
            $this->logger->alert('Audit cannot get full payload as getAuditableData failed', [
                'auditableData' => $auditableDataAware,
                'throwable' => $throwable,
                'code' => 1662200022,
            ]);

            return [];
        }
    }

    /**
     * @return class-string[]
     */
    private function extractAttachableData(AttachmentAwareInterface $auditableDataAware): array
    {
        return \iterable_to_array($auditableDataAware->getAttachments()->map(
            static fn (AttachableInterface $attachable) => $attachable::class
        ));
    }

    private function jsonEncode(array $result): string
    {
        try {
            /** @var string $auditableDataEncoded */
            $auditableDataEncoded = \json_encode($result, \JSON_THROW_ON_ERROR | $this->jsonEncodeFlags);

            return $auditableDataEncoded;
        } catch (\JsonException $jsonError) {
            $this->logger->alert('Audit cannot get full payload due to a json_encode error', [
                'outputLine' => $result,
                'throwable' => $jsonError,
                'code' => 1662200023,
            ]);

            return (string) \json_encode($result, \JSON_PARTIAL_OUTPUT_ON_ERROR | $this->jsonEncodeFlags);
        } catch (\Throwable $throwable) {
            $this->logger->alert('Audit cannot get full payload due to an exception', [
                'outputLine' => $result,
                'throwable' => $throwable,
                'code' => 1662200024,
            ]);

            return (string) \json_encode([
                '$error' => 'An unrecoverable error happened during serialization',
            ], $this->jsonEncodeFlags);
        }
    }
}
