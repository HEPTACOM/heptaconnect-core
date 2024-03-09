<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Core\Exploration\Exception\PortalNodeNotFoundException;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * Start exploration jobs, either instantly or queued.
 */
interface ExploreServiceInterface
{
    /**
     * Run an exploration.
     *
     * @throws PortalNodeNotFoundException
     */
    public function explore(PortalNodeKeyInterface $portalNodeKey, ?EntityTypeCollection $entityTypes = null): void;

    /**
     * Dispatch an exploration as a queued job.
     *
     * @throws PortalNodeNotFoundException
     */
    public function dispatchExploreJob(PortalNodeKeyInterface $portalNodeKey, ?EntityTypeCollection $entityTypes = null): void;
}
