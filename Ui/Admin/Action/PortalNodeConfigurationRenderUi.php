<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorServiceInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationRender\PortalNodeConfigurationRenderResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationRender\PortalNodeConfigurationRenderCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeConfigurationGetUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeConfigurationRenderUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeConfigurationRenderUi implements PortalNodeConfigurationRenderUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeConfigurationGetUiActionInterface $pnConfGetUiAction,
        private PortalNodeConfigurationProcessorServiceInterface $configurationProcessorService
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeConfigurationRenderUiActionInterface::class);
    }

    public function getRendered(PortalNodeConfigurationRenderCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $getCriteria = new PortalNodeConfigurationGetCriteria($criteria->getPortalNodeKeys());

        return $trail->returnIterable(\iterable_map(
            $this->pnConfGetUiAction->get($getCriteria, $context),
            fn (PortalNodeConfigurationGetResult $result): PortalNodeConfigurationRenderResult => new PortalNodeConfigurationRenderResult(
                $result->getPortalNodeKey(),
                $this->configurationProcessorService->applyRead(
                    $result->getPortalNodeKey(),
                    static fn (): array => $result->getConfiguration()
                )
            )
        ));
    }
}
