<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiverStackBuilderFactoryInterface
{
    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityType
     */
    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityType
    ): ReceiverStackBuilderInterface;
}
