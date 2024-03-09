<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\File\Filesystem;

use Heptacom\HeptaConnect\Core\Bridge\File\PortalNodeFilesystemStreamProtocolProviderInterface;
use Heptacom\HeptaConnect\Core\File\Filesystem\RewritePathStreamWrapper;
use Heptacom\HeptaConnect\Core\File\Filesystem\StreamUriSchemePathConverter;
use Heptacom\HeptaConnect\Core\Portal\File\Filesystem\Contract\FilesystemFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Contract\FilesystemInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Http\Message\UriFactoryInterface;

final class FilesystemFactory implements FilesystemFactoryInterface
{
    public function __construct(
        private PortalNodeFilesystemStreamProtocolProviderInterface $streamProtocolProvider,
        private UriFactoryInterface $uriFactory,
        private StorageKeyGeneratorContract $storageKeyGenerator
    ) {
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): FilesystemInterface
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());
        $streamScheme = \strtolower((string) \preg_replace('/[^a-zA-Z0-9]/', '-', 'heptaconnect-' . $key));

        if (!\in_array($streamScheme, \stream_get_wrappers(), true)) {
            $trueProtocol = $this->streamProtocolProvider->provide($portalNodeKey);

            \stream_wrapper_register($streamScheme, RewritePathStreamWrapper::class);
            \stream_context_set_default([
                $streamScheme => [
                    'protocol' => [
                        'set' => $trueProtocol,
                    ],
                ],
            ]);
        }

        return new Filesystem(new StreamUriSchemePathConverter($this->uriFactory, $streamScheme));
    }
}
