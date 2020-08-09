<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistry $normalizationRegistry;

    private StorageInterface $storage;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistry $normalizationRegistry,
        StorageInterface $storage,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->storage = $storage;
        $this->portalNodeKey = $portalNodeKey;
    }

    public function get(string $key)
    {
        if (!$this->storage->hasPortalStorageValue($this->portalNodeKey, $key)) {
            return null;
        }

        $value = $this->storage->getPortalStorageValue($this->portalNodeKey, $key);
        $type = $this->storage->getPortalStorageType($this->portalNodeKey, $key);
        $denormalizer = $this->normalizationRegistry->getDenormalizer($type);

        if (!$denormalizer instanceof DenormalizerInterface) {
            return null;
        }

        if (!$denormalizer->supportsDenormalization($value, $type)) {
            return null;
        }

        return $denormalizer->denormalize($value, $type);
    }

    public function set(string $key, $value): void
    {
        $normalizer = $this->normalizationRegistry->getNormalizer($value);

        if (!$normalizer instanceof NormalizerInterface) {
            return;
        }

        $this->storage->setPortalStorageValue(
            $this->portalNodeKey,
            $key,
            (string) $normalizer->normalize($value),
            $normalizer->getType()
        );
    }

    public function has(string $key): bool
    {
        return $this->storage->hasPortalStorageValue($this->portalNodeKey, $key);
    }

    public function unset(string $key): void
    {
        $this->storage->unsetPortalStorageValue($this->portalNodeKey, $key);
    }

    public function canGet(string $type): bool
    {
        return $this->normalizationRegistry->getDenormalizer($type) instanceof DenormalizerInterface;
    }

    public function canSet(string $type): bool
    {
        return $this->normalizationRegistry->getNormalizerByType($type) instanceof NormalizerInterface;
    }
}
