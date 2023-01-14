<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Storage;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\PortalNodeStorageItemContract;
use Psr\Log\LoggerInterface;

class PortalNodeStorageItemUnpacker
{
    public function __construct(
        private NormalizationRegistryContract $normalizationRegistry,
        private LoggerInterface $logger
    ) {
    }

    public function unpack(PortalNodeStorageItemContract $storageItem): mixed
    {
        $denormalizer = $this->normalizationRegistry->getDenormalizer($storageItem->getType());

        if (!$denormalizer instanceof DenormalizerInterface) {
            $this->logger->error('Failed retrieving a normalizer for a value from the portal storage', [
                'code' => 1631565257,
                'portalNodeKey' => $storageItem->getPortalNodeKey(),
                'key' => $storageItem->getStorageKey(),
            ]);

            return null;
        }

        if (!$denormalizer->supportsDenormalization($storageItem->getValue(), $storageItem->getType())) {
            return null;
        }

        try {
            return $denormalizer->denormalize($storageItem->getValue(), $storageItem->getType());
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed denormalizing a portal storage value', [
                'code' => 1651338621,
                'exception' => $throwable,
                'portalNodeKey' => $storageItem->getPortalNodeKey(),
                'key' => $storageItem->getStorageKey(),
                'type' => $storageItem->getType(),
            ]);
        }

        return null;
    }
}
