<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Exception;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class PortalNodeNotFoundException extends \RuntimeException
{
    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(PortalNodeKeyInterface $portalNodeKey, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Portal by key \'%s\' not found', \json_encode($portalNodeKey)), $code, $previous);
        $this->portalNodeKey = $portalNodeKey;
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeKey;
    }
}
