<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Storage\Base\Action\Route\Delete\RouteDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteRemove\RouteRemoveCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteRemoveUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RouteMissingException;

final class RouteRemoveUi implements RouteRemoveUiActionInterface
{
    private RouteGetActionInterface $routeGetAction;

    private RouteDeleteActionInterface $routeDeleteAction;

    public function __construct(RouteGetActionInterface $routeGetAction, RouteDeleteActionInterface $routeDeleteAction)
    {
        $this->routeGetAction = $routeGetAction;
        $this->routeDeleteAction = $routeDeleteAction;
    }

    public function remove(RouteRemoveCriteria $criteria): void
    {
        /** @var RouteKeyInterface[] $uncheckedRouteKeys */
        $uncheckedRouteKeys = \iterable_to_array($criteria->getRouteKeys());

        try {
            $foundRoutes = $this->routeGetAction->get(new RouteGetCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1659293800, $throwable);
        }

        foreach ($foundRoutes as $foundRoute) {
            $uncheckedRouteKeys = \array_filter(
                $uncheckedRouteKeys,
                static fn (RouteKeyInterface $k): bool => !$k->equals($foundRoute->getRouteKey())
            );
        }

        foreach ($uncheckedRouteKeys as $routeKey) {
            throw new RouteMissingException($routeKey, 1659293801);
        }

        try {
            $this->routeDeleteAction->delete(new RouteDeleteCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1659293802, $throwable);
        }
    }
}
