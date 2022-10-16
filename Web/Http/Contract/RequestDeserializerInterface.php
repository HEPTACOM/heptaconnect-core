<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Psr\Http\Message\RequestInterface;

interface RequestDeserializerInterface
{
    /**
     * Deserialize data, that has been previously been serialized with @see RequestSerializerInterface back into a request.
     */
    public function deserialize(string $requestData): RequestInterface;
}
