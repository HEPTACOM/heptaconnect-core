<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Delete\RouteDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\RouteKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddPayloadCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddResultCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteAddUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RouteAddFailedException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\RouteAlreadyExistsException;

final class RouteAddUi implements RouteAddUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private RouteCreateActionInterface $routeCreateAction,
        private RouteFindActionInterface $routeFindAction,
        private RouteGetActionInterface $routeGetAction,
        private RouteDeleteActionInterface $routeDeleteAction,
        private PortalNodeGetActionInterface $portalNodeGetAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(RouteAddUiActionInterface::class);
    }

    public function add(RouteAddPayloadCollection $payloads, UiActionContextInterface $context): RouteAddResultCollection
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payloads, $context]);

        try {
            $createPayload = $this->validatePayloads($payloads);
        } catch (\Throwable $e) {
            throw $trail->throwable($e);
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

            $requestedScenarios = [];

            foreach ($payloads as $payload) {
                $requestedScenarios[$this->getScenarioFromPayload($payload)] = $payload;
            }

            $createdScenarios = [];

            foreach ($result as $createResult) {
                $createdScenarios[$this->getScenarioFromResult($createResult)] = $createResult;
            }

            $failedScenarios = \array_diff_key($requestedScenarios, $createdScenarios);

            if ($failedScenarios === []) {
                return $trail->return($result);
            }
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1654573098, $throwable));
        }

        throw $trail->throwable($this->revertLogicError($result, new RouteAddPayloadCollection(\array_values($failedScenarios))));
    }

    private function getScenarioFromPayload(RouteAddPayload $payload): string
    {
        return \json_encode([
            $payload->getSourcePortalNodeKey(),
            $payload->getTargetPortalNodeKey(),
            $payload->getEntityType(),
        ], \JSON_THROW_ON_ERROR);
    }

    private function getScenarioFromResult(RouteAddResult $result): string
    {
        return \json_encode([
            $result->getSourcePortalNodeKey(),
            $result->getTargetPortalNodeKey(),
            $result->getEntityType(),
        ], \JSON_THROW_ON_ERROR);
    }

    /**
     * @throws PortalNodesMissingException
     * @throws RouteAlreadyExistsException
     * @throws UnsupportedStorageKeyException
     */
    private function validatePayloads(RouteAddPayloadCollection $payloads): RouteCreatePayloads
    {
        $result = new RouteCreatePayloads();
        $checkedScenarios = [];
        $portalNodeKeys = new PortalNodeKeyCollection();

        /** @var RouteAddPayload $payload */
        foreach ($payloads as $payload) {
            $scenario = $this->getScenarioFromPayload($payload);
            $portalNodeKeys->push([
                $payload->getSourcePortalNodeKey(),
                $payload->getTargetPortalNodeKey(),
            ]);

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

            $result->push([
                new RouteCreatePayload(
                    $payload->getSourcePortalNodeKey(),
                    $payload->getTargetPortalNodeKey(),
                    $payload->getEntityType(),
                    $payload->getCapabilities()
                ),
            ]);
        }

        $portalNodeKeys = $portalNodeKeys->asUnique();
        $foundPortalNodes = $this->portalNodeGetAction->get(new PortalNodeGetCriteria($portalNodeKeys));

        /** @var PortalNodeGetResult $foundPortalNode */
        foreach ($foundPortalNodes as $foundPortalNode) {
            $portalNodeKeys = $portalNodeKeys->filter(
                static fn (PortalNodeKeyInterface $pnKey): bool => !$pnKey->equals($foundPortalNode->getPortalNodeKey())
            );
        }

        if (!$portalNodeKeys->isEmpty()) {
            throw new PortalNodesMissingException($portalNodeKeys, 1654573096);
        }

        return $result;
    }

    private function revertLogicError(
        RouteAddResultCollection $result,
        RouteAddPayloadCollection $failedScenarios
    ): RouteAddFailedException {
        $createdRouteKeys = new RouteKeyCollection($result->column('getRouteKey'));
        $deletedException = null;

        try {
            $this->routeDeleteAction->delete(new RouteDeleteCriteria($createdRouteKeys));
        } catch (\Throwable $failedOnDeletionException) {
            $deletedException = $failedOnDeletionException;
        }

        $stillExistingRoutes = $this->routeGetAction->get(new RouteGetCriteria($createdRouteKeys));
        $failedToRevert = new RouteAddResultCollection();
        $createdAndReverted = new RouteAddResultCollection();

        foreach ($result as $createResult) {
            foreach ($stillExistingRoutes as $stillExistingRoute) {
                if ($createResult->getRouteKey()->equals($stillExistingRoute->getRouteKey())) {
                    $failedToRevert->push([$createResult]);

                    continue 2;
                }

                $createdAndReverted->push([$createResult]);
            }
        }

        return new RouteAddFailedException(
            $failedScenarios,
            $failedToRevert,
            $createdAndReverted,
            1654573097,
            $deletedException
        );
    }
}
