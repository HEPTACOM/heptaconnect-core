<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Storage;

use Heptacom\HeptaConnect\Core\Portal\Storage\Contract\PortalNodeStorageItemPackerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItem;
use Psr\Log\LoggerInterface;

final class PortalNodeStorageItemPacker implements PortalNodeStorageItemPackerInterface
{
    public function __construct(
        private NormalizationRegistryContract $normalizationRegistry,
        private LoggerInterface $logger
    ) {
    }

    public function pack(string $key, mixed $value, ?\DateInterval $ttl): ?PortalNodeStorageSetItem
    {
        $normalizer = $this->normalizationRegistry->getNormalizer($value);

        if (!$normalizer instanceof NormalizerInterface) {
            $this->logger->error('Failed getting a normalizer for a value for storing a value in the portal storage', [
                'code' => 1631562097,
                'key' => $key,
            ]);

            return null;
        }

        try {
            $normalizedValue = $normalizer->normalize($value);
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed normalizing a value for a value for storing it in the portal storage', [
                'code' => 1631562928,
                'exception' => $throwable,
                'key' => $key,
            ]);

            return null;
        }

        if (!\is_string($normalizedValue)) {
            $this->logger->error('Normalization result of a value for storing a value in the portal storage is not a string', [
                'code' => 1631562285,
                'key' => $key,
            ]);

            return null;
        }

        return new PortalNodeStorageSetItem($key, $normalizedValue, $normalizer->getType(), $ttl);
    }
}
