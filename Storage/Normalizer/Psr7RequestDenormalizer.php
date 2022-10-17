<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestDeserializerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Psr\Http\Message\RequestInterface;

final class Psr7RequestDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private RequestDeserializerInterface $deserializer
    ) {
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    /**
     * @param string|null $format
     *
     * @return RequestInterface
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $this->deserializer->deserialize($data);
    }

    /**
     * @param string|null $format
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type !== $this->getType() || !\is_string($data)) {
            return false;
        }

        try {
            $this->denormalize($data, $type, $format);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
