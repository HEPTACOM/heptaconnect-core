<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;

final class Publisher implements PublisherInterface
{
    public function __construct(private JobDispatcherContract $jobDispatcher)
    {
    }

    public function publishBatch(MappingComponentCollection $mappings): void
    {
        /** @var Emission[] $jobs */
        $jobs = \iterable_to_array($mappings->map(static fn (MappingComponentStruct $mapping, $_): Emission => new Emission($mapping)));

        if ($jobs === []) {
            return;
        }

        $this->jobDispatcher->dispatch(new JobCollection($jobs));
    }
}
