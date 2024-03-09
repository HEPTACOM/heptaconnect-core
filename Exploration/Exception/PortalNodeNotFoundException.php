<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Exception;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class PortalNodeNotFoundException extends \RuntimeException
{
    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(PortalNodeKeyInterface $portalNodeKey, int $code = 0, ?\Throwable $previous = null)
    {
        try {
            $jsonEncodedKey = \json_encode($portalNodeKey, \JSON_THROW_ON_ERROR);
        } catch (\Throwable $throwable) {
            $jsonEncodedKey = (string) \json_encode($portalNodeKey, \JSON_PARTIAL_OUTPUT_ON_ERROR);
            $previous ??= $throwable;
        }

        parent::__construct(\sprintf('Portal by key \'%s\' not found', $jsonEncodedKey), $code, $previous);
        $this->portalNodeKey = $portalNodeKey;
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeKey;
    }
}
