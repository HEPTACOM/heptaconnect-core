<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;

class SerializableCompressDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $serializableDenormalizer;

    public function __construct(DenormalizerInterface $serializableDenormalizer)
    {
        $this->serializableDenormalizer = $serializableDenormalizer;
    }

    public function getType(): string
    {
        return $this->serializableDenormalizer->getType() . '+gzpress';
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $this->serializableDenormalizer->denormalize(
            \gzuncompress($data),
            $this->serializableDenormalizer->getType(),
            $format,
            $context
        );
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === $this->getType() &&
            $this->serializableDenormalizer->supportsDenormalization(
                \gzuncompress($data),
                $this->serializableDenormalizer->getType(),
                $format
            );
    }
}
