<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestSerializerInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Exception\RequestSerializationException;
use Psr\Http\Message\RequestInterface;

final class RequestSerializer implements RequestSerializerInterface
{
    public function serialize(RequestInterface $request): string
    {
        try {
            return \json_encode([
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'requestTarget' => $request->getRequestTarget(),
                'protocolVersion' => $request->getProtocolVersion(),
                'headers' => $request->getHeaders(),
                'body' => (string) $request->getBody(),
            ], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        } catch (\Throwable $jsonError) {
            throw new RequestSerializationException($request, 1666451010, $jsonError);
        }
    }
}
