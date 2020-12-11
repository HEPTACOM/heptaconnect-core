<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class CronjobContext extends AbstractPortalNodeContext implements CronjobContextInterface
{
    private CronjobInterface $cronjob;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        PortalNodeKeyInterface $portalNodeKey,
        CronjobInterface $cronjob
    ) {
        parent::__construct(
            $configurationService,
            $portalRegistry,
            $portalStorageFactory,
            $resourceLocking,
            $portalStackServiceContainerFactory,
            $portalNodeKey
        );
        $this->cronjob = $cronjob;
    }

    public function getCronjob(): CronjobInterface
    {
        return $this->cronjob;
    }
}
