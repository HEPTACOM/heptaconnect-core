<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

class ExplorationHandler
{
    private ExploreServiceInterface $exploreService;

    public function __construct(ExploreServiceInterface $exploreService)
    {
        $this->exploreService = $exploreService;
    }

    public function triggerExploration(JobDataCollection $jobs): void
    {
        /** @var JobData $job */
        foreach ($jobs as $job) {
            $this->exploreService->explore(
                $job->getMappingComponent()->getPortalNodeKey(),
                [$job->getMappingComponent()->getDatasetEntityClassName()]
            );
        }
    }
}
