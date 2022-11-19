<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\File\Filesystem;

use Heptacom\HeptaConnect\Core\Bridge\File\PortalNodeFilesystemStreamWrapperFactoryInterface;
use Heptacom\HeptaConnect\Core\File\Filesystem\StreamUriSchemePathConverter;
use Heptacom\HeptaConnect\Core\File\Filesystem\StreamWrapperRegistry;
use Heptacom\HeptaConnect\Core\Portal\File\Filesystem\Contract\FilesystemFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Contract\FilesystemInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Http\Message\UriFactoryInterface;

final class FilesystemFactory implements FilesystemFactoryInterface
{
    private PortalNodeFilesystemStreamWrapperFactoryInterface $streamWrapperFactory;

    private UriFactoryInterface $uriFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        PortalNodeFilesystemStreamWrapperFactoryInterface $streamWrapperFactory,
        UriFactoryInterface $uriFactory,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->streamWrapperFactory = $streamWrapperFactory;
        $this->uriFactory = $uriFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): FilesystemInterface
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);
        $streamScheme = \strtolower(\preg_replace('/[^a-zA-Z0-9]/', '-', 'heptaconnect-' . $key));
        $pathConverter = new StreamUriSchemePathConverter($this->uriFactory, $streamScheme);

        if (!\in_array($streamScheme, \stream_get_wrappers(), true)) {
            $streamWrapper = new UriToPathConvertingStreamWrapper();
            $streamWrapper->setDecorated($this->streamWrapperFactory->create($portalNodeKey));
            $streamWrapper->setConverter($pathConverter);

            StreamWrapperRegistry::register($streamScheme, $streamWrapper);
        }

        return new Filesystem($pathConverter);
    }
}
