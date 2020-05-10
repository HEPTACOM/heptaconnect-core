<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class SourcePortalNodeStamp implements StampInterface
{
    private string $portalNodeId;

    public function __construct(string $portalNodeId)
    {
        $this->portalNodeId = $portalNodeId;
    }

    public function getPortalNodeId(): string
    {
        return $this->portalNodeId;
    }
}
