<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Support;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasFindActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeAliasNotFoundException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Support\PortalNodeAliasResolverInterface;

final class PortalNodeAliasResolver implements PortalNodeAliasResolverInterface
{
    public function __construct(private PortalNodeAliasFindActionInterface $findAction)
    {
    }

    public function resolve(string $portalNodeAlias): PortalNodeKeyInterface
    {
        $criteria = new PortalNodeAliasFindCriteria([$portalNodeAlias]);

        foreach ($this->findAction->find($criteria) as $findResult) {
            return $findResult->getPortalNodeKey();
        }

        throw new PortalNodeAliasNotFoundException($portalNodeAlias, 1655051115);
    }
}
