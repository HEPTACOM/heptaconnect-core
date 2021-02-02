<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Core\Exploration\Exception\PortalNodeNotFoundException;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExploreServiceInterface
{
    /**
     * @psalm-param array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>>|null $dataTypes
     *
     * @throws PortalNodeNotFoundException
     */
    public function explore(PortalNodeKeyInterface $portalNodeKey, ?array $dataTypes = null): void;
}
