<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Formatter\Support;

use Heptacom\HeptaConnect\Core\Web\Http\Formatter\Support\Contract\HeaderUtilityInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HeaderUtility implements HeaderUtilityInterface
{
    public function sortResponseHeaders(ResponseInterface $response): ResponseInterface
    {
        $headers = $response->getHeaders();
        $headers = $this->sortHeaders($headers);

        foreach (\array_keys($headers) as $header) {
            $response = $response->withoutHeader((string) $header);
        }

        foreach ($headers as $header => $values) {
            $response = $response->withHeader((string) $header, $values);
        }

        return $response;
    }

    public function sortRequestHeaders(RequestInterface $request): RequestInterface
    {
        $headers = $request->getHeaders();
        $headers = $this->sortHeaders($headers);

        foreach (\array_keys($headers) as $header) {
            $request = $request->withoutHeader((string) $header);
        }

        foreach ($headers as $header => $values) {
            $request = $request->withHeader((string) $header, $values);
        }

        return $request;
    }

    /**
     * @param string[][] $headers
     * @return string[][]
     */
    private function sortHeaders(array $headers): array
    {
        $normalizedKeys = \array_combine(\array_keys($headers), \array_keys($headers));
        $normalizedKeys = \array_change_key_case($normalizedKeys, \CASE_LOWER);

        \ksort($headers);

        if (isset($normalizedKeys['host'])) {
            $hostCorrectCase = $normalizedKeys['host'];
            $host = $headers[$hostCorrectCase];
            unset($headers[$hostCorrectCase]);
            $headers = [$hostCorrectCase => $host] + $headers;
        }

        return $headers;
    }
}
