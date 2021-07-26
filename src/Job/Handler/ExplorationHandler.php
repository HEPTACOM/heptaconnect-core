<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;

class ExplorationHandler
{
    private ExploreServiceInterface $exploreService;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        ExploreServiceInterface $exploreService,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->exploreService = $exploreService;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function triggerExplorations(JobDataCollection $jobs): void
    {
        $keys = [];
        $types = [];

        /** @var MappingComponentStructContract $mapping */
        foreach ($jobs->column('getMappingComponent') as $mapping) {
            $key = $this->storageKeyGenerator->serialize($mapping->getPortalNodeKey());

            $keys[$key] = $mapping->getPortalNodeKey();
            $types[$key][] = $mapping->getDatasetEntityClassName();
        }

        foreach ($keys as $key => $portalNodeKey) {
            $type = $types[$key] ?? [];

            if ($type === []) {
                continue;
            }

            $this->exploreService->explore($portalNodeKey, $type);
        }
    }
}
