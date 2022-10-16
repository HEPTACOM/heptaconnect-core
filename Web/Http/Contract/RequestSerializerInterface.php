<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Psr\Http\Message\RequestInterface;

interface RequestSerializerInterface
{
    /**
     * Serializes a request into a string to store it and deserialize again with @see RequestDeserializerInterface
     */
    public function serialize(RequestInterface $request): string;
}
