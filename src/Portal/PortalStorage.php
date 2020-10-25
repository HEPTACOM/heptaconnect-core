<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistry $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistry $normalizationRegistry,
        PortalStorageContract $portalStorage,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
        $this->portalNodeKey = $portalNodeKey;
    }

    public function get(string $key)
    {
        if (!$this->portalStorage->has($this->portalNodeKey, $key)) {
            return null;
        }

        $value = $this->portalStorage->getValue($this->portalNodeKey, $key);
        $type = $this->portalStorage->getType($this->portalNodeKey, $key);
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

        $this->portalStorage->set(
            $this->portalNodeKey,
            $key,
            (string) $normalizer->normalize($value),
            $normalizer->getType()
        );
    }

    public function list(): iterable
    {
        foreach ($this->portalStorage->list($this->portalNodeKey) as $key => $item) {
            $value = $item['value'];
            $type = $item['type'];

            $denormalizer = $this->normalizationRegistry->getDenormalizer($type);

            if (!$denormalizer instanceof DenormalizerInterface) {
                continue;
            }

            if (!$denormalizer->supportsDenormalization($value, $type)) {
                continue;
            }

            yield $key => $denormalizer->denormalize($value, $type);
        }
    }

    public function has(string $key): bool
    {
        return $this->portalStorage->has($this->portalNodeKey, $key);
    }

    public function unset(string $key): void
    {
        $this->portalStorage->unset($this->portalNodeKey, $key);
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
