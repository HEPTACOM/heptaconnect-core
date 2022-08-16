<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\ClassStringReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionTypeCollection;
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
        if ($payload->getPortalExtensionQueries()->count() < 1) {
            return;
        }

        $portalNodeKey = $payload->getPortalNodeKey();
        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalExtensionDeactivatePayload = new PortalExtensionDeactivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions()->bySupport($portalNodeGetResult->getPortalClass());
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);
            $alreadyInactiveExtensions = new PortalExtensionTypeCollection();

            /** @var ClassStringReferenceContract $query */
            foreach ($payload->getPortalExtensionQueries() as $query) {
                $queriedPortalExtensions = $this->packageQueryMatcher->matchPortalExtensions((string) $query, $portalExtensions);

                if ($queriedPortalExtensions->count() === 0) {
                    throw new NoMatchForPackageQueryException((string) $query, 1650731999);
                }

                /** @var PortalExtensionContract $portalExtension */
                foreach ($queriedPortalExtensions as $portalExtension) {
                    $portalExtensionType = $portalExtension::class();

                    if ($portalExtensionState->isActive($portalExtension) && !$alreadyInactiveExtensions->has($portalExtensionType)) {
                        $portalExtensionDeactivatePayload->addExtension($portalExtensionType);
                    } else {
                        $alreadyInactiveExtensions->push([$portalExtensionType]);
                    }
                }
            }

            if ($portalExtensionDeactivatePayload->getExtensions()->count() < 1) {
                throw new PortalExtensionsAreAlreadyInactiveOnPortalNodeException($portalNodeKey, $alreadyInactiveExtensions, 1650732000);
            }

            $this->portalExtensionDeactivateAction->deactivate($portalExtensionDeactivatePayload);

            return;
        }

        throw new PortalNodeMissingException($portalNodeKey, 1650732001);
    }
}
