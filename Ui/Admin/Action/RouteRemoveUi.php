<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Delete\RouteDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteRemove\RouteRemoveCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteRemoveUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RoutesMissingException;

final class RouteRemoveUi implements RouteRemoveUiActionInterface
{
    private AuditTrailFactoryInterface $auditTrailFactory;

    private RouteGetActionInterface $routeGetAction;

    private RouteDeleteActionInterface $routeDeleteAction;

    public function __construct(
        AuditTrailFactoryInterface $auditTrailFactory,
        RouteGetActionInterface $routeGetAction,
        RouteDeleteActionInterface $routeDeleteAction
    ) {
        $this->auditTrailFactory = $auditTrailFactory;
        $this->routeGetAction = $routeGetAction;
        $this->routeDeleteAction = $routeDeleteAction;
    }

    public static function class(): UiActionType
    {
        return new UiActionType(RouteRemoveUiActionInterface::class);
    }

    public function remove(RouteRemoveCriteria $criteria, UiActionContextInterface $context): void
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $uncheckedRouteKeys = $criteria->getRouteKeys()->asUnique();

        try {
            $foundRoutes = $this->routeGetAction->get(new RouteGetCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1659293800, $throwable));
        }

        foreach ($foundRoutes as $foundRoute) {
            $uncheckedRouteKeys = $uncheckedRouteKeys->filter(
                static fn (RouteKeyInterface $k): bool => !$k->equals($foundRoute->getRouteKey())
            );
        }

        if (!$uncheckedRouteKeys->isEmpty()) {
            throw $trail->throwable(new RoutesMissingException($uncheckedRouteKeys, 1659293801));
        }

        try {
            $this->routeDeleteAction->delete(new RouteDeleteCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1659293802, $throwable));
        }

        $trail->end();
    }
}
