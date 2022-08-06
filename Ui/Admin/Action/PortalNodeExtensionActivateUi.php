<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\ClassStringReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionTypeCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionActivate\PortalNodeExtensionActivatePayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionActivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\NoMatchForPackageQueryException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionsAreAlreadyActiveOnPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodeMissingException;

final class PortalNodeExtensionActivateUi implements PortalNodeExtensionActivateUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    private PortalExtensionActivateActionInterface $portalExtensionActivateAction;

    private PackageQueryMatcherInterface $packageQueryMatcher;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction,
        PortalExtensionActivateActionInterface $portalExtensionActivateAction,
        PackageQueryMatcherInterface $packageQueryMatcher,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
        $this->portalExtensionActivateAction = $portalExtensionActivateAction;
        $this->packageQueryMatcher = $packageQueryMatcher;
        $this->portalLoader = $portalLoader;
    }

    public function activate(PortalNodeExtensionActivatePayload $payload): void
    {
        if ($payload->getPortalExtensionQueries()->count() < 1) {
            return;
        }

        $portalNodeKey = $payload->getPortalNodeKey();
        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalExtensionActivePayload = new PortalExtensionActivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions()->bySupport($portalNodeGetResult->getPortalClass());
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);
            $alreadyActiveExtensions = new PortalExtensionTypeCollection();

            /** @var ClassStringReferenceContract $query */
            foreach ($payload->getPortalExtensionQueries() as $query) {
                $queriedPortalExtensions = $this->packageQueryMatcher->matchPortalExtensions((string) $query, $portalExtensions);

                if ($queriedPortalExtensions->count() === 0) {
                    throw new NoMatchForPackageQueryException((string) $query, 1650142326);
                }

                /** @var PortalExtensionContract $portalExtension */
                foreach ($queriedPortalExtensions as $portalExtension) {
                    $portalExtensionType = $portalExtension::class();

                    if ($portalExtensionState->isActive($portalExtension) && !$alreadyActiveExtensions->has($portalExtensionType)) {
                        $alreadyActiveExtensions->push([$portalExtensionType]);
                    } else {
                        $portalExtensionActivePayload->addExtension($portalExtensionType);
                    }
                }
            }

            if ($portalExtensionActivePayload->getExtensions()->count() < 1) {
                throw new PortalExtensionsAreAlreadyActiveOnPortalNodeException($portalNodeKey, $alreadyActiveExtensions, 1650142327);
            }

            $this->portalExtensionActivateAction->activate($portalExtensionActivePayload);

            return;
        }

        throw new PortalNodeMissingException($portalNodeKey, 1650142328);
    }
}
