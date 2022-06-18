<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\RouteKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddPayloadCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddResultCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteAddUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RouteAlreadyExistsException;

final class RouteAddUi implements RouteAddUiActionInterface
{
    private RouteCreateActionInterface $routeCreateAction;

    private RouteFindActionInterface $routeFindAction;

    private RouteGetActionInterface $routeGetAction;

    private PortalNodeGetActionInterface $portalNodeGetAction;

    public function __construct(
        RouteCreateActionInterface $routeCreateAction,
        RouteFindActionInterface $routeFindAction,
        RouteGetActionInterface $routeGetAction,
        PortalNodeGetActionInterface $portalNodeGetAction
    ) {
        $this->routeCreateAction = $routeCreateAction;
        $this->routeFindAction = $routeFindAction;
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->routeGetAction = $routeGetAction;
    }

    public function add(RouteAddPayloadCollection $payloads): RouteAddResultCollection
    {
        $createPayload = new RouteCreatePayloads();
        $checkedScenarios = [];
        $portalNodeKeys = [];

        /** @var RouteAddPayload $payload */
        foreach ($payloads as $payload) {
            $scenario = \json_encode([
                $payload->getSourcePortalNodeKey(),
                $payload->getTargetPortalNodeKey(),
                $payload->getEntityType(),
            ]);
            $portalNodeKeys[\json_encode($payload->getSourcePortalNodeKey())] = $payload->getSourcePortalNodeKey();
            $portalNodeKeys[\json_encode($payload->getTargetPortalNodeKey())] = $payload->getTargetPortalNodeKey();

            if ($checkedScenarios[$scenario] ?? false) {
                continue;
            }

            $checkedScenarios[$scenario] = true;

            $routeFindCriteria = new RouteFindCriteria(
                $payload->getSourcePortalNodeKey(),
                $payload->getTargetPortalNodeKey(),
                $payload->getEntityType()
            );

            $foundRoute = $this->routeFindAction->find($routeFindCriteria);

            if ($foundRoute instanceof RouteFindResult) {
                throw new RouteAlreadyExistsException($foundRoute->getRouteKey(), 1654573095);
            }

            $createPayload->push([
                new RouteCreatePayload(
                    $payload->getSourcePortalNodeKey(),
                    $payload->getTargetPortalNodeKey(),
                    $payload->getEntityType(),
                    $payload->getCapabilities()
                ),
            ]);
        }

        $foundPortalNodes = $this->portalNodeGetAction->get(
            new PortalNodeGetCriteria(new PortalNodeKeyCollection(\array_values($portalNodeKeys)))
        );

        /** @var PortalNodeGetResult $foundPortalNode */
        foreach ($foundPortalNodes as $foundPortalNode) {
            unset($portalNodeKeys[\json_encode($foundPortalNode->getPortalNodeKey())]);
        }

        if ($portalNodeKeys !== []) {
            throw new PortalNodeMissingException(\current($portalNodeKeys), 1654573096);
        }

        try {
            $routeCreateResults = $this->routeCreateAction->create($createPayload);
            $routeGetCriteria = new RouteGetCriteria(new RouteKeyCollection($routeCreateResults->column('getRouteKey')));
            $routeGetResults = $this->routeGetAction->get($routeGetCriteria);
            $result = new RouteAddResultCollection(\iterable_map(
                $routeGetResults,
                static fn (RouteGetResult $result): RouteAddResult => new RouteAddResult(
                    $result->getRouteKey(),
                    $result->getSourcePortalNodeKey(),
                    $result->getTargetPortalNodeKey(),
                    $result->getEntityType(),
                    $result->getCapabilities(),
                )
            ));

            if ($result->count() !== $createPayload->count()) {
                throw new PersistException(1654573097);
            }

            return $result;
        } catch (\Throwable $throwable) {
            throw new PersistException(1654573098, $throwable);
        }
    }
}
