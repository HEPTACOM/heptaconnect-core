<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Formatter\Support\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Tooling around HTTP header handling.
 */
interface HeaderUtilityInterface
{
    /**
     * Sorts the headers of a response alphabetically but puts "host" entry first for better readability.
     */
    public function sortResponseHeaders(ResponseInterface $response): ResponseInterface;

    /**
     * Sorts the headers of a request alphabetically but puts "host" entry first for better readability.
     */
    public function sortRequestHeaders(RequestInterface $request): RequestInterface;
}
