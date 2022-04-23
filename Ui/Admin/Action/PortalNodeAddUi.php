<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\ReadException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeAdd\PortalNodeAddPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeAdd\PortalNodeAddResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeAddUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeAliasIsAlreadyAssignedException;

class PortalNodeAddUi implements PortalNodeAddUiActionInterface
{
    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    private PortalNodeAliasFindActionInterface $portalNodeAliasFindAction;

    public function __construct(
        PortalNodeCreateActionInterface $portalNodeCreateAction,
        PortalNodeAliasFindActionInterface $portalNodeAliasFindAction
    ) {
        $this->portalNodeCreateAction = $portalNodeCreateAction;
        $this->portalNodeAliasFindAction = $portalNodeAliasFindAction;
    }

    public function add(PortalNodeAddPayload $payload): PortalNodeAddResult
    {
        $alias = $payload->getPortalNodeAlias();

        if ($alias !== null) {
            try {
                $foundAliases = $this->portalNodeAliasFindAction->find(new PortalNodeAliasFindCriteria([$alias]));
            } catch (ReadException $e) {
                throw new PersistException(1650718860, $e);
            }

            foreach ($foundAliases as $foundAlias) {
                throw new PortalNodeAliasIsAlreadyAssignedException($foundAlias->getAlias(), $foundAlias->getPortalNodeKey(), 1650718861);
            }
        }

        try {
            $createdPortalNodes = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
                new PortalNodeCreatePayload($payload->getPortalClass(), $payload->getPortalNodeAlias()),
            ]));
        } catch (\Throwable $throwable) {
            throw new PersistException(1650718862, $throwable);
        }

        /** @var PortalNodeCreateResult $createdPortalNode */
        foreach ($createdPortalNodes as $createdPortalNode) {
            return new PortalNodeAddResult($createdPortalNode->getPortalNodeKey());
        }

        throw new PersistException(1650718863);
    }
}
