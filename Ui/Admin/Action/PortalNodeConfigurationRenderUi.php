<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract\PortalNodeExistenceSeparatorInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationRender\PortalNodeConfigurationRenderCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationRender\PortalNodeConfigurationRenderResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeConfigurationRenderUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeConfigurationRenderUi implements PortalNodeConfigurationRenderUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeExistenceSeparatorInterface $portalNodeExistenceSeparator,
        private ConfigurationServiceInterface $configurationService
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeConfigurationRenderUiActionInterface::class);
    }

    public function getRendered(PortalNodeConfigurationRenderCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);

        $separation = $this->portalNodeExistenceSeparator->separateKeys($criteria->getPortalNodeKeys());
        $separation->throwWhenKeysAreMissing($trail);

        $keys = new PortalNodeKeyCollection();
        $keys->push($separation->getExistingKeys());
        $keys->push($separation->getPreviewKeys());

        return $trail->returnIterable($keys->map(
            fn (PortalNodeKeyInterface $portalNodeKey): PortalNodeConfigurationRenderResult => new PortalNodeConfigurationRenderResult(
                $portalNodeKey,
                $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
            )
        ));
    }
}
