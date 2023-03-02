<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract\PortalNodeExistenceSeparatorInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria as StoragePortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeConfigurationGetUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;

final class PortalNodeConfigurationGetUi implements PortalNodeConfigurationGetUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeExistenceSeparatorInterface $portalNodeExistenceSeparator,
        private PortalNodeConfigurationGetActionInterface $configurationGetAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeConfigurationGetUiActionInterface::class);
    }

    public function get(PortalNodeConfigurationGetCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);

        try {
            $separation = $this->portalNodeExistenceSeparator->separateKeys($criteria->getPortalNodeKeys());
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1670832600, $throwable));
        }

        $separation->throwWhenKeysAreMissing($trail);

        foreach ($separation->getPreviewKeys() as $previewKey) {
            yield $trail->yield(new PortalNodeConfigurationGetResult($previewKey, []));
        }

        if (!$separation->getExistingKeys()->isEmpty()) {
            $fetchedPortalNodeKeys = new PortalNodeKeyCollection();

            try {
                $criteria = new StoragePortalNodeConfigurationGetCriteria($separation->getExistingKeys());

                foreach ($this->configurationGetAction->get($criteria) as $configuration) {
                    $fetchedPortalNodeKeys->push([$configuration->getPortalNodeKey()]);

                    yield $trail->yield(new PortalNodeConfigurationGetResult(
                        $configuration->getPortalNodeKey(),
                        $configuration->getValue()
                    ));
                }
            } catch (\Throwable $throwable) {
                throw $trail->throwable(new ReadException(1670832602, $throwable));
            }

            $notFetchedPortalNodeKeys = $criteria->getPortalNodeKeys()->filter(
                static fn (PortalNodeKeyInterface $pnKey): bool => !$fetchedPortalNodeKeys->contains($pnKey)
            );

            foreach ($notFetchedPortalNodeKeys as $notFetchedPortalNodeKey) {
                yield $trail->yield(new PortalNodeConfigurationGetResult($notFetchedPortalNodeKey, []));
            }
        }

        $trail->end();
    }
}
