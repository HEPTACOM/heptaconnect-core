<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;


use DateTime;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Playground\Dataset\Bottle;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class ExplorationHandler
{

    private ExploreServiceInterface $exploreService;

    public function __construct(ExploreServiceInterface $exploreService)
    {
        $this->exploreService = $exploreService;
    }

    public function triggerExploration(MappingComponentStructContract $mapping) : bool {

        $this->exploreService->explore($mapping->getPortalNodeKey(), [$mapping->getDatasetEntityClassName()]);

        return true;
    }
}
