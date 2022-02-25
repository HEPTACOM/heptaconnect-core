<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\ExplorationHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Start\JobStartPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;

class ExplorationHandler implements ExplorationHandlerInterface
{
    private ExploreServiceInterface $exploreService;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private JobStartActionInterface $jobStartAction;

    private JobFinishActionInterface $jobFinishAction;

    public function __construct(
        ExploreServiceInterface $exploreService,
        StorageKeyGeneratorContract $storageKeyGenerator,
        JobStartActionInterface $jobStartAction,
        JobFinishActionInterface $jobFinishAction
    ) {
        $this->exploreService = $exploreService;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->jobStartAction = $jobStartAction;
        $this->jobFinishAction = $jobFinishAction;
    }

    public function triggerExplorations(JobDataCollection $jobs): void
    {
        $keys = [];
        $types = [];
        $jobKeys = [];

        foreach ($jobs as $job) {
            $mapping = $job->getMappingComponent();
            $key = $this->storageKeyGenerator->serialize($mapping->getPortalNodeKey());

            $keys[$key] = $mapping->getPortalNodeKey();
            $types[$key][] = $mapping->getEntityType();
            $jobKeys[$key][] = $job->getJobKey();
        }

        foreach ($keys as $key => $portalNodeKey) {
            $type = $types[$key] ?? [];

            if ($type === []) {
                continue;
            }

            $jobKeys = new JobKeyCollection($jobKeys[$key]);

            $this->jobStartAction->start(new JobStartPayload($jobKeys, new \DateTimeImmutable(), null));
            $this->exploreService->explore($portalNodeKey, $type);
            $this->jobFinishAction->finish(new JobFinishPayload($jobKeys, new \DateTimeImmutable(), null));
        }
    }
}
