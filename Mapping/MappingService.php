<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingExceptionRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class MappingService implements MappingServiceInterface
{
    private MappingExceptionRepositoryContract $mappingExceptionRepository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private LoggerInterface $logger;

    public function __construct(
        MappingExceptionRepositoryContract $mappingExceptionRepository,
        StorageKeyGeneratorContract $storageKeyGenerator,
        LoggerInterface $logger
    ) {
        $this->mappingExceptionRepository = $mappingExceptionRepository;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->logger = $logger;
    }

    public function addException(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        \Throwable $exception
    ): void {
        try {
            $this->mappingExceptionRepository->create($portalNodeKey, $mappingNodeKey, $exception);
        } catch (\Throwable $throwable) {
            $this->logger->error('MAPPING_EXCEPTION', [
                'exception' => $exception,
                'mappingNodeKey' => $this->storageKeyGenerator->serialize($mappingNodeKey),
                'portalNodeKey' => $this->storageKeyGenerator->serialize($portalNodeKey),
                'outerException' => $throwable,
            ]);
        }
    }
}
