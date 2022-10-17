<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Filesystem;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use League\Flysystem\FilesystemInterface;

class FilesystemFactory
{
    public function __construct(private StorageKeyGeneratorContract $storageKeyGenerator, private FilesystemInterface $filesystem)
    {
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): FilesystemInterface
    {
        $portalNodeId = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());
        /** @var string $portalNodeId */
        $portalNodeId = \preg_replace('/[^a-zA-Z0-9]/', '_', $portalNodeId);

        return new PrefixFilesystem($this->filesystem, $portalNodeId);
    }
}
