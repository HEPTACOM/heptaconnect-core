<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditableDataSerializerInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachmentAwareInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Audit\AuditableDataAwareInterface;
use Psr\Log\LoggerInterface;

final class AuditableDataSerializer implements AuditableDataSerializerInterface
{
    private LoggerInterface $logger;

    private int $jsonEncodeFlags;

    public function __construct(LoggerInterface $logger, int $jsonEncodeFlags = \JSON_UNESCAPED_SLASHES)
    {
        $this->logger = $logger;
        $this->jsonEncodeFlags = $jsonEncodeFlags;
    }

    public function serialize(AuditableDataAwareInterface $auditableDataAware): string
    {
        $auditableData = [];

        try {
            $auditableData['data'] = $auditableDataAware->getAuditableData();
        } catch (\Throwable $throwable) {
            $this->logger->alert('Audit cannot get full payload as getAuditableData failed', [
                'auditableData' => $auditableData,
                'throwable' => $throwable,
                'code' => 1662200022,
            ]);

            $auditableData['data'] = [];
        }

        if ($auditableDataAware instanceof AttachmentAwareInterface) {
            $auditableData['attachedTypes'] = \iterable_to_array($auditableDataAware->getAttachments()->map(
                static fn (AttachableInterface $attachable) => \get_class($attachable)
            ));
        }

        try {
            $auditableDataEncoded = \json_encode($auditableData, \JSON_THROW_ON_ERROR | $this->jsonEncodeFlags);

            \assert(\is_string($auditableDataEncoded));
        } catch (\JsonException $jsonError) {
            $this->logger->alert('Audit cannot get full payload due to a json_encode error', [
                'outputLine' => $auditableData,
                'throwable' => $jsonError,
                'code' => 1662200023,
            ]);
            $auditableDataEncoded = (string) \json_encode($auditableData, \JSON_PARTIAL_OUTPUT_ON_ERROR | $this->jsonEncodeFlags);
        } catch (\Throwable $throwable) {
            $this->logger->alert('Audit cannot get full payload due to an exception', [
                'outputLine' => $auditableData,
                'throwable' => $throwable,
                'code' => 1662200024,
            ]);
            $auditableDataEncoded = (string) \json_encode([
                '$error' => 'An unrecoverable error happened during serialization',
            ], $this->jsonEncodeFlags);
        }

        return $auditableDataEncoded;
    }
}
