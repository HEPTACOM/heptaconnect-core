<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Overview\OverviewCriteriaContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteBrowse\RouteBrowseCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteBrowse\RouteBrowseResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\BrowseCriteriaContract;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteBrowseUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\UnsupportedSortingException;

final class RouteBrowseUi implements RouteBrowseUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private RouteOverviewActionInterface $routeOverviewAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(RouteBrowseUiActionInterface::class);
    }

    public function browse(RouteBrowseCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $storageCriteria = new RouteOverviewCriteria();

        $storageCriteria->setPage($criteria->getPage() ?? 0);
        $storageCriteria->setPageSize($criteria->getPageSize());
        $storageCriteria->setCapabilityFilter($criteria->getCapabilityFilter());
        $storageCriteria->setSourcePortalNodeKeyFilter($criteria->getSourcePortalNodeKeyFilter());
        $storageCriteria->setTargetPortalNodeKeyFilter($criteria->getTargetPortalNodeKeyFilter());
        $storageCriteria->setEntityTypeFilter($criteria->getEntityTypeFilter());

        $storageSorting = [];

        foreach ($criteria->getSort() as $field => $direction) {
            $parsedDirection = [
                BrowseCriteriaContract::SORT_ASC => OverviewCriteriaContract::SORT_ASC,
                BrowseCriteriaContract::SORT_DESC => OverviewCriteriaContract::SORT_DESC,
            ][$direction] ?? null;
            $parsedField = [
                RouteBrowseCriteria::FIELD_CREATED => RouteOverviewCriteria::FIELD_CREATED,
                RouteBrowseCriteria::FIELD_ENTITY_TYPE => RouteOverviewCriteria::FIELD_ENTITY_TYPE,
                RouteBrowseCriteria::FIELD_SOURCE => RouteOverviewCriteria::FIELD_SOURCE,
                RouteBrowseCriteria::FIELD_TARGET => RouteOverviewCriteria::FIELD_TARGET,
            ][$field] ?? null;

            if (!\is_string($parsedDirection)) {
                throw $trail->throwable(new UnsupportedSortingException(
                    $direction,
                    new StringCollection([
                        BrowseCriteriaContract::SORT_ASC,
                        BrowseCriteriaContract::SORT_DESC,
                    ]),
                    1670625000
                ));
            }

            if (!\is_string($parsedField)) {
                throw $trail->throwable(throw new UnsupportedSortingException(
                    $field,
                    new StringCollection([
                        RouteBrowseCriteria::FIELD_CREATED,
                        RouteBrowseCriteria::FIELD_ENTITY_TYPE,
                        RouteBrowseCriteria::FIELD_SOURCE,
                        RouteBrowseCriteria::FIELD_TARGET,
                    ]),
                    1670625001
                ));
            }

            $storageSorting[$parsedField] = $parsedDirection;
        }

        if ($storageSorting === []) {
            $storageSorting[RouteOverviewCriteria::FIELD_CREATED] = OverviewCriteriaContract::SORT_DESC;
        }

        $storageCriteria->setSort($storageSorting);

        foreach ($this->routeOverviewAction->overview($storageCriteria) as $storageResult) {
            yield $trail->yield(new RouteBrowseResult(
                $storageResult->getRouteKey(),
                $storageResult->getSourcePortalNodeKey(),
                $storageResult->getTargetPortalNodeKey(),
                $storageResult->getEntityType(),
                $storageResult->getCapabilities()
            ));
        }

        $trail->end();
    }
}
