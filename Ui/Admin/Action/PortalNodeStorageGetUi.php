<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStorageGet\PortalNodeStorageGetCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStorageGet\PortalNodeStorageGetResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeStorageGetUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\InvalidPortalNodeStorageValueException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;
use Psr\SimpleCache\InvalidArgumentException;

final class PortalNodeStorageGetUi implements PortalNodeStorageGetUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalStorageFactory $portalStorageFactory
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeStorageGetUiActionInterface::class);
    }

    public function get(PortalNodeStorageGetCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $portalNodeKey = $criteria->getPortalNodeKey();

        if (!$portalNodeKey instanceof PreviewPortalNodeKey) {
            try {
                $this->validatePortalNodeKey($portalNodeKey);
            } catch (\Throwable $e) {
                throw $trail->throwable($e);
            }
        }

        $keys = $criteria->getStorageKeys();

        if ($keys->isEmpty()) {
            return $trail->returnIterable([]);
        }

        return $trail->returnIterable($this->collectValues($portalNodeKey, $keys));
    }

    /**
     * @throws InvalidPortalNodeStorageValueException
     * @throws ReadException
     *
     * @return iterable<array-key, PortalNodeStorageGetResult>
     */
    private function collectValues(PortalNodeKeyInterface $portalNodeKey, StringCollection $keys): iterable
    {
        $storage = $this->portalStorageFactory->createPortalStorage($portalNodeKey);

        try {
            foreach ($storage->getMultiple($keys) as $key => $value) {
                if (!\is_scalar($value) && $value !== null) {
                    throw new InvalidPortalNodeStorageValueException($portalNodeKey, (string) $key, 1673129102);
                }

                yield new PortalNodeStorageGetResult($portalNodeKey, (string) $key, $value);
            }
        } catch (InvalidPortalNodeStorageValueException $throwable) {
            throw $throwable;
        } catch (\Throwable|InvalidArgumentException $throwable) {
            throw new ReadException(1673129103, $throwable);
        }
    }

    /**
     * @throws PortalNodesMissingException
     * @throws ReadException
     */
    private function validatePortalNodeKey(PortalNodeKeyInterface $portalNodeKey): void
    {
        $pnKeysToLoad = new PortalNodeKeyCollection([$portalNodeKey]);
        $portalNodeGetCriteria = new PortalNodeGetCriteria($pnKeysToLoad);

        try {
            $gotPortalNodeKeys = new PortalNodeKeyCollection(\iterable_map(
                $this->portalNodeGetAction->get($portalNodeGetCriteria),
                static fn (PortalNodeGetResult $result): PortalNodeKeyInterface => $result->getPortalNodeKey()
            ));
        } catch (\Throwable $throwable) {
            throw new ReadException(1673129100, $throwable);
        }

        $missingPortalNodes = new PortalNodeKeyCollection($pnKeysToLoad->filter(
            static fn (PortalNodeKeyInterface $pnKey): bool => !$gotPortalNodeKeys->contains($pnKey)
        )->getIterator());

        if (!$missingPortalNodes->isEmpty()) {
            throw new PortalNodesMissingException($missingPortalNodes, 1673129101);
        }
    }
}
