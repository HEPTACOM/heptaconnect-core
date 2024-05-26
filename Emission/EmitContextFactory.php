<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityError\IdentityErrorCreateActionInterface;

final readonly class EmitContextFactory implements EmitContextFactoryInterface
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private PortalStackServiceContainerFactory $portalStackContainerFactory,
        private IdentityErrorCreateActionInterface $identityErrorCreateAction
    ) {
    }

    #[\Override]
    public function createContext(PortalNodeKeyInterface $portalNodeKey, bool $directEmission = false): EmitContextInterface
    {
        return new EmitContext(
            $this->portalStackContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey),
            $this->identityErrorCreateAction,
            $directEmission
        );
    }
}
