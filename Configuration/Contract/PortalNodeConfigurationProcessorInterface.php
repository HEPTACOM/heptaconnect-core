<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * Processor interface to intercept portal node configuration reading and writing..
 */
interface PortalNodeConfigurationProcessorInterface
{
    /**
     * Processes portal node configuration between loading from database and creating @see ConfigurationContract
     *
     * @param \Closure(): array $read
     */
    public function read(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array;

    /**
     * Processes portal node configuration before writing to database.
     *
     * @param \Closure(array): void $write
     */
    public function write(PortalNodeKeyInterface $portalNodeKey, array $payload, \Closure $write): void;
}
