<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Deactivate\PortalExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionDeactivate\PortalNodeExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionDeactivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\NoMatchForPackageQueryException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionsAreAlreadyInactiveOnPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;

final class PortalNodeExtensionDeactivateUi implements PortalNodeExtensionDeactivateUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    private PortalExtensionDeactivateActionInterface $portalExtensionDeactivateAction;

    private PackageQueryMatcherInterface $packageQueryMatcher;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction,
        PortalExtensionDeactivateActionInterface $portalExtensionDeactivateAction,
        PackageQueryMatcherInterface $packageQueryMatcher,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
        $this->portalExtensionDeactivateAction = $portalExtensionDeactivateAction;
        $this->packageQueryMatcher = $packageQueryMatcher;
        $this->portalLoader = $portalLoader;
    }

    public function deactivate(PortalNodeExtensionDeactivatePayload $payload): void
    {
        if ($payload->getPortalExtensionQueries() === []) {
            return;
        }

        $portalNodeKey = $payload->getPortalNodeKey();
        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalExtensionDeactivatePayload = new PortalExtensionDeactivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions()->bySupport($portalNodeGetResult->getPortalClass());
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);
            $alreadyInactiveExtensions = [];

            foreach ($payload->getPortalExtensionQueries() as $query) {
                $queriedPortalExtensions = $this->packageQueryMatcher->matchPortalExtensions($query, $portalExtensions);

                if ($queriedPortalExtensions->count() === 0) {
                    throw new NoMatchForPackageQueryException($query, 1650731999);
                }

                foreach ($queriedPortalExtensions as $portalExtension) {
                    $portalExtensionClass = \get_class($portalExtension);

                    if ($portalExtensionState->isActive($portalExtension)) {
                        $portalExtensionDeactivatePayload->addExtension($portalExtensionClass);
                    } else {
                        $alreadyInactiveExtensions[] = $portalExtensionClass;
                    }
                }
            }

            if ($portalExtensionDeactivatePayload->getExtensions() === []) {
                throw new PortalExtensionsAreAlreadyInactiveOnPortalNodeException($portalNodeKey, $alreadyInactiveExtensions, 1650732000);
            }

            $this->portalExtensionDeactivateAction->deactivate($portalExtensionDeactivatePayload);

            return;
        }

        throw new PortalNodeMissingException($portalNodeKey, 1650732001);
    }
}
