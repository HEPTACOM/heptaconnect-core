<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Deactivate\PortalExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionDeactivate\PortalNodeExtensionDeactivatePayloads;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionDeactivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionDoesNotSupportPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionIsAlreadyInactiveOnPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;

final class PortalNodeExtensionDeactivateUi implements PortalNodeExtensionDeactivateUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    private PortalExtensionDeactivateActionInterface $portalExtensionDeactivateAction;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction,
        PortalExtensionDeactivateActionInterface $portalExtensionDeactivateAction,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
        $this->portalExtensionDeactivateAction = $portalExtensionDeactivateAction;
        $this->portalLoader = $portalLoader;
    }

    public function deactivate(PortalNodeExtensionDeactivatePayloads $payloads): void
    {
        $portalNodeKey = $payloads->getPortalNodeKey();
        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalExtensionActivePayload = new PortalExtensionDeactivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions();
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);

            foreach ($payloads->getPortalExtensionClasses() as $portalExtensionClass) {
                if (!\class_exists($portalExtensionClass)) {
                    throw new PortalExtensionMissingException($portalExtensionClass, 1650731999);
                }

                $portalClass = $portalNodeGetResult->getPortalClass();
                $portalExtension = $this->getPortalExtensionByType($portalExtensions->bySupport($portalClass), $portalExtensionClass);

                if ($portalExtension === null) {
                    throw new PortalExtensionDoesNotSupportPortalNodeException($portalNodeKey, $portalExtensionClass, 1650732000);
                }

                if (!$portalExtensionState->isActive($portalExtension)) {
                    throw new PortalExtensionIsAlreadyInactiveOnPortalNodeException($portalNodeKey, $portalExtensionClass, 1650732001);
                }

                $portalExtensionActivePayload->addExtension($portalExtensionClass);
            }

            if ($portalExtensionActivePayload->getExtensions() === []) {
                return;
            }

            $this->portalExtensionDeactivateAction->deactivate($portalExtensionActivePayload);

            return;
        }

        throw new PortalNodeMissingException($portalNodeKey, 1650732002);
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
