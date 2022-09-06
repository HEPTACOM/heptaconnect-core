<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Transition;

use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\ExploredPrimaryKeysToJobsConverterInterface;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class ExploredPrimaryKeysToEmissionJobsConverter implements ExploredPrimaryKeysToJobsConverterInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function convert(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType,
        StringCollection $primaryKeys
    ): JobCollection {
        $result = new JobCollection($primaryKeys->map(static fn (string $pk): Emission => new Emission(
            new MappingComponentStruct($portalNodeKey, $entityType, $pk)
        )));

        if ($result->count() < 1) {
            $this->logger->warning('Primary key conversion to emission jobs created no jobs', [
                'entityType' => $entityType,
                'portalNode' => $portalNodeKey,
                'code' => 1661091901,
            ]);
        }

        return $result;
    }
}
