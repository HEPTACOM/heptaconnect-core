<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalNodeExtensionCollection;

class PortalRegistry
{
    private ComposerPortalLoader $portalLoader;

    /** @var PortalNodeInterface[]|null */
    private ?array $portals = null;

    private ?PortalNodeExtensionCollection $portalExtensions = null;

    public function __construct(ComposerPortalLoader $portalLoader)
    {
        $this->portalLoader = $portalLoader;
    }

    /**
     * @return PortalNodeInterface[]
     */
    public function getPortals(): array
    {
        if (\is_null($this->portals)) {
            /** @var PortalNodeInterface[] $portals */
            $portals = iterable_to_array($this->portalLoader->getPortals());
            $this->portals = $portals;
        }

        return $this->portals;
    }

    public function getPortalExtensions(): PortalNodeExtensionCollection
    {
        if (\is_null($this->portalExtensions)) {
            $this->portalExtensions = $this->portalLoader->getPortalExtensions();
        }

        return $this->portalExtensions;
    }
}
