<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract\PortalNodeExistenceSeparatorInterface;
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
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionDeactivateUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\NoMatchForPackageQueryException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalExtensionsAreAlreadyInactiveOnPortalNodeException;

final class PortalNodeExtensionDeactivateUi implements PortalNodeExtensionDeactivateUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeExistenceSeparatorInterface $portalNodeExistenceSeparator,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalExtensionFindActionInterface $portalExtensionFindAction,
        private PortalExtensionDeactivateActionInterface $portalExtensionDeactivateAction,
        private PackageQueryMatcherInterface $packageQueryMatcher,
        private ComposerPortalLoader $portalLoader
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeExtensionDeactivateUiActionInterface::class);
    }

    public function deactivate(PortalNodeExtensionDeactivatePayload $payload, UiActionContextInterface $context): void
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payload, $context]);

        if ($payload->getPortalExtensionQueries()->count() < 1) {
            $trail->end();

            return;
        }

        $separation = $this->portalNodeExistenceSeparator->separateKeys(
            new PortalNodeKeyCollection([$payload->getPortalNodeKey()])
        );

        $separation->throwWhenKeysAreMissing($trail);
        $separation->throwWhenPreviewKeysAreGiven($trail);

        $portalNodeGetCriteria = new PortalNodeGetCriteria($separation->getExistingKeys());

        foreach ($this->portalNodeGetAction->get($portalNodeGetCriteria) as $portalNodeGetResult) {
            $portalNodeKey = $portalNodeGetResult->getPortalNodeKey();
            $portalExtensionDeactivatePayload = new PortalExtensionDeactivatePayload($portalNodeKey);
            $portalExtensions = $this->portalLoader->getPortalExtensions()->bySupport($portalNodeGetResult->getPortalClass());
            $portalExtensionState = $this->portalExtensionFindAction->find($portalNodeKey);
            $alreadyInactiveExtensions = new PortalExtensionTypeCollection();

            /** @var ClassStringReferenceContract $query */
            foreach ($payload->getPortalExtensionQueries() as $query) {
                $queriedPortalExtensions = $this->packageQueryMatcher->matchPortalExtensions((string) $query, $portalExtensions);

                if ($queriedPortalExtensions->count() === 0) {
                    throw $trail->throwable(new NoMatchForPackageQueryException((string) $query, 1650731999));
                }

                /** @var PortalExtensionContract $portalExtension */
                foreach ($queriedPortalExtensions as $portalExtension) {
                    $portalExtensionType = $portalExtension::class();

                    if ($portalExtensionState->isActive($portalExtension) && !$alreadyInactiveExtensions->contains($portalExtensionType)) {
                        $portalExtensionDeactivatePayload->addExtension($portalExtensionType);
                    } else {
                        $alreadyInactiveExtensions->push([$portalExtensionType]);
                    }
                }
            }

            if ($portalExtensionDeactivatePayload->getExtensions()->count() < 1) {
                throw $trail->throwable(new PortalExtensionsAreAlreadyInactiveOnPortalNodeException($portalNodeKey, $alreadyInactiveExtensions, 1650732000));
            }

            $this->portalExtensionDeactivateAction->deactivate($portalExtensionDeactivatePayload);
        }

        $trail->end();
    }
}
