<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionBrowse\PortalNodeExtensionBrowseCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionBrowse\PortalNodeExtensionBrowseResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionBrowseUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeExtensionBrowseUi implements PortalNodeExtensionBrowseUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalExtensionFindActionInterface $portalExtensionFindAction,
        private ComposerPortalLoader $portalLoader
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeExtensionBrowseUiActionInterface::class);
    }

    public function browse(PortalNodeExtensionBrowseCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $itemsLeft = $criteria->getPageSize();
        $page = $criteria->getPage();
        $itemsToSkip = $page !== null ? (($page - 1) * $criteria->getPageSize()) : 0;

        foreach ($this->iterateOverItems($criteria->getPortalNodeKey()) as $item) {
            if ($this->shouldSkipItem($criteria, $item, $itemsToSkip)) {
                continue;
            }

            if ($itemsLeft === 0 && $page !== null) {
                break;
            }

            --$itemsLeft;

            yield $trail->yield($item);
        }

        $trail->end();
    }

    /**
     * @return iterable<int, PortalNodeExtensionBrowseResult>
     */
    private function iterateOverItems(PortalNodeKeyInterface $portalNodeKey): iterable
    {
        $findResult = $this->portalExtensionFindAction->find($portalNodeKey);
        $portalNodeGetResults = $this->portalNodeGetAction->get(new PortalNodeGetCriteria(new PortalNodeKeyCollection([
            $portalNodeKey,
        ])));

        foreach ($portalNodeGetResults as $portalNodeGetResult) {
            $supportedExtensions = $this->portalLoader->getPortalExtensions()->bySupport($portalNodeGetResult->getPortalClass());

            /** @var PortalExtensionContract $extension */
            foreach ($supportedExtensions as $extension) {
                $isActive = $findResult->isActive($extension);

                yield new PortalNodeExtensionBrowseResult($portalNodeKey, $isActive, $extension::class());
            }
        }
    }

    private function shouldSkipItem(
        PortalNodeExtensionBrowseCriteria $criteria,
        PortalNodeExtensionBrowseResult $item,
        int &$itemsToSkip
    ): bool {
        if (!$criteria->getShowActive() && $item->getActive()) {
            return true;
        }

        if (!$criteria->getShowInactive() && !$item->getActive()) {
            return true;
        }

        if ($itemsToSkip > 0) {
            --$itemsToSkip;

            return true;
        }

        return false;
    }
}
