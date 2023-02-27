<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Support;

use Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract\PortalNodeExistenceSeparatorInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;

final class PortalNodeExistenceSeparator implements PortalNodeExistenceSeparatorInterface
{
    public function __construct(
        private PortalNodeGetActionInterface $getAction
    ) {
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    public function separateKeys(PortalNodeKeyCollection $portalNodeKeys): PortalNodeExistenceSeparationResult
    {
        $preview = new PortalNodeKeyCollection();
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection());

        foreach ($portalNodeKeys->asUnique() as $portalNodeKey) {
            if ($portalNodeKey instanceof PreviewPortalNodeKey) {
                $preview->push([$portalNodeKey]);
            } else {
                $criteria->getPortalNodeKeys()->push([$portalNodeKey]);
            }
        }

        $found = new PortalNodeKeyCollection();

        if (!$criteria->getPortalNodeKeys()->isEmpty()) {
            $found->push(\iterable_map(
                $this->getAction->get($criteria),
                static fn (PortalNodeGetResult $getResult): PortalNodeKeyInterface => $getResult->getPortalNodeKey()
            ));
        }
        $notFound = $criteria->getPortalNodeKeys()->filter(
            static fn (PortalNodeKeyInterface $lookedFor): bool => !$found->contains($lookedFor)
        );

        return new PortalNodeExistenceSeparationResult($preview, $found, $notFound);
    }
}
