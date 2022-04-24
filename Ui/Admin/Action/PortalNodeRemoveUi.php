<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeRemove\PortalNodeRemoveCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeRemoveUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;

final class PortalNodeRemoveUi implements PortalNodeRemoveUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalNodeDeleteActionInterface $portalNodeDeleteAction;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalNodeDeleteActionInterface $portalNodeDeleteAction
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalNodeDeleteAction = $portalNodeDeleteAction;
    }

    public function remove(PortalNodeRemoveCriteria $criteria): void
    {
        /** @var PortalNodeKeyInterface[] $uncheckedPortalNodeKeys */
        $uncheckedPortalNodeKeys = \iterable_to_array($criteria->getPortalNodeKeys());

        try {
            $foundPortalNodes = $this->portalNodeGetAction->get(new PortalNodeGetCriteria($criteria->getPortalNodeKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1650758000, $throwable);
        }

        foreach ($foundPortalNodes as $foundPortalNode) {
            $uncheckedPortalNodeKeys = \array_filter(
                $uncheckedPortalNodeKeys,
                static fn (PortalNodeKeyInterface $k): bool => !$k->equals($foundPortalNode->getPortalNodeKey())
            );
        }

        foreach ($uncheckedPortalNodeKeys as $portalNodeKey) {
            throw new PortalNodeMissingException($portalNodeKey, 1650758001);
        }

        try {
            $this->portalNodeDeleteAction->delete(new PortalNodeDeleteCriteria($criteria->getPortalNodeKeys()));
        } catch (\Throwable $throwable) {
            throw new PersistException(1650758002, $throwable);
        }
    }
}
