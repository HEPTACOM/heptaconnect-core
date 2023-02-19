<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Support\PortalNodeExistenceSeparator;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStorageGet\PortalNodeStorageGetCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStorageGet\PortalNodeStorageGetResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeStorageGetUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\InvalidPortalNodeStorageValueException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;
use Psr\SimpleCache\InvalidArgumentException;

final class PortalNodeStorageGetUi implements PortalNodeStorageGetUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeExistenceSeparator $portalNodeExistenceSeparator,
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

        try {
            $separation = $this->portalNodeExistenceSeparator->separateKeys(new PortalNodeKeyCollection([$criteria->getPortalNodeKey()]));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1673129100, $throwable));
        }

        $separation->throwWhenKeysAreMissing($trail);

        $keys = $criteria->getStorageKeys();

        if ($keys->isEmpty()) {
            return $trail->returnIterable([]);
        }

        return $trail->returnIterable($this->collectValues($criteria->getPortalNodeKey(), $keys));
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
}
