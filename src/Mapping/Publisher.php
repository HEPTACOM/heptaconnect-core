<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class Publisher implements PublisherInterface
{
    private JobDispatcherContract $jobDispatcher;

    public function __construct(JobDispatcherContract $jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    public function publish(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeId,
        string $externalId
    ): void {
        $this->jobDispatcher->dispatch(new JobCollection([
            new Emission(new MappingComponentStruct(
                $portalNodeId, $datasetEntityClassName, $externalId
            )),
        ]));
    }

    public function publishBatch(MappingCollection $mappings): void
    {
        $jobs = [];

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $jobs[] = new Emission(new MappingComponentStruct(
                $mapping->getPortalNodeKey(), $mapping->getDatasetEntityClassName(), $mapping->getExternalId()
            ));
        }

        if ($jobs === []) {
            return;
        }

        $this->jobDispatcher->dispatch(new JobCollection($jobs));
    }
}
