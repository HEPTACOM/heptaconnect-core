<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
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
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionActivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\NoMatchForPackageQueryException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionsAreAlreadyActiveOnPortalNodeException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;

final class PortalNodeExtensionActivateUi implements PortalNodeExtensionActivateUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalExtensionFindActionInterface $portalExtensionFindAction,
        private PortalExtensionActivateActionInterface $portalExtensionActivateAction,
        private PackageQueryMatcherInterface $packageQueryMatcher,
        private ComposerPortalLoader $portalLoader
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeExtensionActivateUiActionInterface::class);
    }

    public function activate(PortalNodeExtensionActivatePayload $payload, UiActionContextInterface $context): void
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payload, $context]);

        if ($payload->getPortalExtensionQueries()->count() < 1) {
            $trail->end();

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
                    throw $trail->throwable(new NoMatchForPackageQueryException((string) $query, 1650142326));
                }

                /** @var PortalExtensionContract $portalExtension */
                foreach ($queriedPortalExtensions as $portalExtension) {
                    $portalExtensionType = $portalExtension::class();

                    if ($portalExtensionState->isActive($portalExtension) && !$alreadyActiveExtensions->contains($portalExtensionType)) {
                        $alreadyActiveExtensions->push([$portalExtensionType]);
                    } else {
                        $portalExtensionActivePayload->addExtension($portalExtensionType);
                    }
                }
            }

            if ($portalExtensionActivePayload->getExtensions()->count() < 1) {
                throw $trail->throwable(new PortalExtensionsAreAlreadyActiveOnPortalNodeException($portalNodeKey, $alreadyActiveExtensions, 1650142327));
            }

            $this->portalExtensionActivateAction->activate($portalExtensionActivePayload);
            $trail->end();

            return;
        }

        throw $trail->throwable(new PortalNodesMissingException(new PortalNodeKeyCollection([$portalNodeKey]), 1650142328));
    }
}
