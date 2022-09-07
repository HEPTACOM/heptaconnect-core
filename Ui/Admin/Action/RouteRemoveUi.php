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
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RoutesMissingException;

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
        $uncheckedRouteKeys = $criteria->getRouteKeys()->asUnique();

        try {
            $foundRoutes = $this->routeGetAction->get(new RouteGetCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1659293800, $throwable);
        }

        foreach ($foundRoutes as $foundRoute) {
            $uncheckedRouteKeys = $uncheckedRouteKeys->filter(
                static fn (RouteKeyInterface $k): bool => !$k->equals($foundRoute->getRouteKey())
            );
        }

        if (!$uncheckedRouteKeys->isEmpty()) {
            throw new RoutesMissingException($uncheckedRouteKeys, 1659293801);
        }

        try {
            $this->routeDeleteAction->delete(new RouteDeleteCriteria($criteria->getRouteKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1659293802, $throwable);
        }
    }
}
