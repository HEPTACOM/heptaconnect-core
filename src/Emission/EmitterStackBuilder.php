<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class EmitterStackBuilder implements EmitterStackBuilderInterface
{
    private PortalRegistryInterface $portalRegistry;

    private PortalNodeKeyInterface $portalNodeKey;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityClassName;

    /**
     * @var EmitterContract[]
     */
    private array $emitters = [];

    private ?PortalContract $cachedPortal = null;

    private ?PortalExtensionCollection $cachedPortalExtensions = null;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityClassName
     */
    public function __construct(
        PortalRegistryInterface $portalRegistry,
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalNodeKey = $portalNodeKey;
        $this->entityClassName = $entityClassName;
    }

    public function pushSource(): self
    {
        $lastEmitter = null;

        foreach ($this->getPortal()->getEmitters()->bySupport($this->entityClassName) as $emitter) {
            $lastEmitter = $emitter;
        }

        if ($lastEmitter instanceof EmitterContract) {
            $this->emitters[] = $lastEmitter;
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->getPortalExtensions()->getEmitterDecorators()->bySupport($this->entityClassName) as $emitter) {
            $this->emitters[] = $emitter;
        }

        return $this;
    }

    public function build(): EmitterStackInterface
    {
        return new EmitterStack(\array_map(static fn (EmitterContract $e) => clone $e, $this->emitters));
    }

    public function isEmpty(): bool
    {
        return empty($this->emitters);
    }

    private function getPortal(): PortalContract
    {
        return $this->cachedPortal ??= $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    private function getPortalExtensions(): PortalExtensionCollection
    {
        return $this->cachedPortalExtensions ??= $this->portalRegistry->getPortalExtensions($this->portalNodeKey);
    }
}
