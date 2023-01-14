<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Support;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;

final class PortalNodeExistenceSeparationResult
{
    public function __construct(
        private PortalNodeKeyCollection $previewKeys,
        private PortalNodeKeyCollection $existingKeys,
        private PortalNodeKeyCollection $notFoundKeys
    ) {
    }

    public function getPreviewKeys(): PortalNodeKeyCollection
    {
        return $this->previewKeys;
    }

    public function getExistingKeys(): PortalNodeKeyCollection
    {
        return $this->existingKeys;
    }

    public function getNotFoundKeys(): PortalNodeKeyCollection
    {
        return $this->notFoundKeys;
    }

    /**
     * @throws PortalNodesMissingException
     */
    public function throwWhenKeysAreMissing(AuditTrailInterface $trail): void
    {
        $missing = $this->getNotFoundKeys();

        if (!$missing->isEmpty()) {
            throw $trail->throwable(new PortalNodesMissingException($missing, 1650732001));
        }
    }

    /**
     * @throws PortalNodesMissingException
     */
    public function throwWhenPreviewKeysAreGiven(AuditTrailInterface $trail): void
    {
        $previews = $this->getPreviewKeys();

        if (!$previews->isEmpty()) {
            throw $trail->throwable(new PortalNodesMissingException($previews, 1650732002));
        }
    }
}
