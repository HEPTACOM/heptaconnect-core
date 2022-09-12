<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\ReadException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeAdd\PortalNodeAddPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeAdd\PortalNodeAddResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeAddUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeAliasIsAlreadyAssignedException;

final class PortalNodeAddUi implements PortalNodeAddUiActionInterface
{
    private AuditTrailFactoryInterface $auditTrailFactory;

    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    private PortalNodeAliasFindActionInterface $portalNodeAliasFindAction;

    public function __construct(
        AuditTrailFactoryInterface $auditTrailFactory,
        PortalNodeCreateActionInterface $portalNodeCreateAction,
        PortalNodeAliasFindActionInterface $portalNodeAliasFindAction
    ) {
        $this->auditTrailFactory = $auditTrailFactory;
        $this->portalNodeCreateAction = $portalNodeCreateAction;
        $this->portalNodeAliasFindAction = $portalNodeAliasFindAction;
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeAddUiActionInterface::class);
    }

    public function add(PortalNodeAddPayload $payload, UiActionContextInterface $context): PortalNodeAddResult
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payload, $context]);
        $alias = $payload->getPortalNodeAlias();

        if ($alias !== null) {
            try {
                $foundAliases = $this->portalNodeAliasFindAction->find(new PortalNodeAliasFindCriteria([$alias]));
            } catch (ReadException $e) {
                throw $trail->throwable(new PersistException(1650718860, $e));
            }

            foreach ($foundAliases as $foundAlias) {
                throw $trail->throwable(new PortalNodeAliasIsAlreadyAssignedException($foundAlias->getAlias(), $foundAlias->getPortalNodeKey(), 1650718861));
            }
        }

        try {
            $createdPortalNodes = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
                new PortalNodeCreatePayload($payload->getPortalClass(), $payload->getPortalNodeAlias()),
            ]));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1650718862, $throwable));
        }

        /** @var PortalNodeCreateResult $createdPortalNode */
        foreach ($createdPortalNodes as $createdPortalNode) {
            $trail->end();

            return new PortalNodeAddResult($createdPortalNode->getPortalNodeKey());
        }

        throw $trail->throwable(new PersistException(1650718863));
    }
}
