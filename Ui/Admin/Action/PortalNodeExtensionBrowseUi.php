<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionBrowse\PortalNodeExtensionBrowseCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeExtensionBrowse\PortalNodeExtensionBrowseResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeExtensionBrowseUiActionInterface;

final class PortalNodeExtensionBrowseUi implements PortalNodeExtensionBrowseUiActionInterface
{
    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
        $this->portalLoader = $portalLoader;
    }

    public function browse(PortalNodeExtensionBrowseCriteria $criteria): iterable
    {
        $itemsLeft = $criteria->getPageSize();
        $page = $criteria->getPage();
        $skipFilter = $this->getSkipFilter($criteria);

        foreach ($this->iterateOverItems($criteria->getPortalNodeKey()) as $item) {
            if ($skipFilter($item)) {
                continue;
            }

            if ($itemsLeft === 0 && $page !== null) {
                break;
            }

            --$itemsLeft;

            yield $item;
        }
    }

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

    private function getSkipFilter(PortalNodeExtensionBrowseCriteria $criteria): \Closure
    {
        $result = static fn (PortalNodeExtensionBrowseResult $item): bool => false;

        if (!$criteria->getShowActive()) {
            $oldResult = $result;
            $result = static fn (PortalNodeExtensionBrowseResult $item): bool => $item->getActive() || $oldResult($item);
        }

        if (!$criteria->getShowInactive()) {
            $oldResult = $result;
            $result = static fn (PortalNodeExtensionBrowseResult $item): bool => !$item->getActive() || $oldResult($item);
        }

        $page = $criteria->getPage();
        $itemsToSkip = $page !== null ? (($page - 1) * $criteria->getPageSize()) : 0;

        return static function (PortalNodeExtensionBrowseResult $item) use (&$itemsToSkip, $result): bool {
            if ($result($item)) {
                return true;
            }

            if ($itemsToSkip > 0) {
                --$itemsToSkip;

                return true;
            }

            return false;
        };
    }
}
