<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteBrowse\RouteBrowseCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteBrowse\RouteBrowseResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteBrowseUiActionInterface;

final class RouteBrowseUi implements RouteBrowseUiActionInterface
{
    private RouteOverviewActionInterface $routeOverviewAction;

    public function __construct(RouteOverviewActionInterface $routeOverviewAction)
    {
        $this->routeOverviewAction = $routeOverviewAction;
    }

    public function browse(RouteBrowseCriteria $criteria): iterable
    {
        $storageCriteria = new RouteOverviewCriteria();

        $storageCriteria->setPage($criteria->getPage() ?? 0);
        $storageCriteria->setPageSize($criteria->getPageSize());
        $storageCriteria->setSort([
            RouteOverviewCriteria::FIELD_CREATED => RouteOverviewCriteria::SORT_DESC,
        ]);
        $storageCriteria->setCapabilityFilter($criteria->getCapabilityFilter());
        $storageCriteria->setSourcePortalNodeKeyFilter($criteria->getSourcePortalNodeKeyFilter());
        $storageCriteria->setTargetPortalNodeKeyFilter($criteria->getTargetPortalNodeKeyFilter());
        $storageCriteria->setEntityTypeFilter($criteria->getEntityTypeFilter());

        foreach ($this->routeOverviewAction->overview($storageCriteria) as $storageResult) {
            yield new RouteBrowseResult(
                $storageResult->getRouteKey(),
                $storageResult->getSourcePortalNodeKey(),
                $storageResult->getTargetPortalNodeKey(),
                $storageResult->getEntityType(),
                $storageResult->getCapabilities()
            );
        }
    }
}
