<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class Psr7RequestNormalizer implements NormalizerInterface
{
    public function supportsNormalization($data, ?string $format = null)
    {
        return $data instanceof RequestInterface;
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    /**
     * @return string
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        if (!$object instanceof RequestInterface) {
            throw new InvalidArgumentException(
                'Psr7RequestNormalizer can only normalize request objects. Got: ' . \get_class($object),
                1647789809
            );
        }

        return \json_encode([
            'method' => $object->getMethod(),
            'uri' => (string) $object->getUri(),
            'requestTarget' => $object->getRequestTarget(),
            'protocolVersion' => $object->getProtocolVersion(),
            'headers' => $object->getHeaders(),
            'body' => (string) $object->getBody(),
        ], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
