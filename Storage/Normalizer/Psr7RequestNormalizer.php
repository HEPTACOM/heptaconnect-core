<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestSerializerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class Psr7RequestNormalizer implements NormalizerInterface
{
    public function __construct(private RequestSerializerInterface $serializer)
    {
    }

    /**
     * @param string|null $format
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof RequestInterface;
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    /**
     * @param string|null $format
     *
     * @return string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof RequestInterface) {
            throw new InvalidArgumentException(
                'Psr7RequestNormalizer can only normalize request objects. Got: ' . $object::class,
                1647789809
            );
        }

        return $this->serializer->serialize($object);
    }
}
