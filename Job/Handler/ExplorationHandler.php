<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\ExplorationHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;

class ExplorationHandler implements ExplorationHandlerInterface
{
    private ExploreServiceInterface $exploreService;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private JobRepositoryContract $jobRepository;

    public function __construct(
        ExploreServiceInterface $exploreService,
        StorageKeyGeneratorContract $storageKeyGenerator,
        JobRepositoryContract $jobRepository
    ) {
        $this->exploreService = $exploreService;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->jobRepository = $jobRepository;
    }

    public function triggerExplorations(JobDataCollection $jobs): void
    {
        $keys = [];
        $types = [];
        $jobKeys = [];

        /** @var JobData $job */
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

            $now = new \DateTimeImmutable();

            foreach ($jobKeys[$key] as $jobKey) {
                $this->jobRepository->start($jobKey, $now);
            }

            $this->exploreService->explore($portalNodeKey, $type);

            $now = new \DateTimeImmutable();

            foreach ($jobKeys[$key] as $jobKey) {
                $this->jobRepository->finish($jobKey, $now);
            }
        }
    }
}
