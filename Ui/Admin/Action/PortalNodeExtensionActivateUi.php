<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionActivate\PortalNodeExtensionActivatePayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionActivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionIsAlreadyActiveOnPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionDoesNotSupportPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;

final class PortalNodeExtensionActivateUi implements PortalNodeExtensionActivateUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    private PortalExtensionActivateActionInterface $portalExtensionActivateAction;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction,
        PortalExtensionActivateActionInterface $portalExtensionActivateAction,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
        $this->portalExtensionActivateAction = $portalExtensionActivateAction;
        $this->portalLoader = $portalLoader;
    }

    public function activate(PortalNodeExtensionActivatePayload $payloads): void
    {
        $portalNodeKey = $payloads->getPortalNodeKey();
        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalExtensionActivePayload = new PortalExtensionActivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions();
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);

            foreach ($payloads->getPortalExtensionClasses() as $portalExtensionClass) {
                if (!\class_exists($portalExtensionClass)) {
                    throw new PortalExtensionMissingException($portalExtensionClass, 1650142325);
                }

                $portalClass = $portalNodeGetResult->getPortalClass();
                $portalExtension = $this->getPortalExtensionByType($portalExtensions->bySupport($portalClass), $portalExtensionClass);

                if ($portalExtension === null) {
                    throw new PortalExtensionDoesNotSupportPortalNodeException($portalNodeKey, $portalExtensionClass, 1650142326);
                }

                if ($portalExtensionState->isActive($portalExtension)) {
                    throw new PortalExtensionIsAlreadyActiveOnPortalNodeException($portalNodeKey, $portalExtensionClass, 1650142327);
                }

                $portalExtensionActivePayload->addExtension($portalExtensionClass);
            }

            if ($portalExtensionActivePayload->getExtensions() === []) {
                return;
            }

            $this->portalExtensionActivateAction->activate($portalExtensionActivePayload);

            return;
        }

        throw new PortalNodeMissingException($portalNodeKey, 1650142328);
    }

    /**
     * @param class-string<PortalExtensionContract> $portalExtensionClass
     */
    private function getPortalExtensionByType(
        PortalExtensionCollection $extensions,
        string $portalExtensionClass
    ): ?PortalExtensionContract {
        foreach ($extensions as $portalExtension) {
            if ($portalExtension instanceof $portalExtensionClass) {
                return $portalExtension;
            }
        }

        return null;
    }
}
