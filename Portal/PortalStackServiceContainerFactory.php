<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\File\FileReferenceResolver;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceResolverContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Container\ContainerInterface;

class PortalStackServiceContainerFactory
{
    private PortalRegistryInterface $portalRegistry;

    private PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $portalContainers = [];

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalStackServiceContainerBuilder = $portalStackServiceContainerBuilder;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): ContainerInterface
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);
        $result = $this->portalContainers[$key] ?? null;

        if ($result instanceof ContainerInterface) {
            return $result;
        }

        $result = $this->portalStackServiceContainerBuilder->build(
            $this->portalRegistry->getPortal($portalNodeKey),
            $this->portalRegistry->getPortalExtensions($portalNodeKey),
            $portalNodeKey
        );
        $result->compile();
        $this->portalContainers[$key] = $result;

        $fileReferenceResolver = $result->get(FileReferenceResolverContract::class);

        if ($fileReferenceResolver instanceof FileReferenceResolver) {
            /** @var HttpClientContract $httpClient */
            $httpClient = $result->get(HttpClientContract::class);
            $fileReferenceResolver->setHttpClient($httpClient);
        }

        return $result;
    }
}
