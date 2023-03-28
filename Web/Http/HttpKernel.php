<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpKernel implements HttpKernelInterface
{
    private PortalNodeKeyInterface $portalNodeKey;

    private HttpHandleServiceInterface $httpHandleService;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        HttpHandleServiceInterface $httpHandleService
    ) {
        $this->portalNodeKey = $portalNodeKey;
        $this->httpHandleService = $httpHandleService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->getRequestWithQueryParams($request);
        $request = $this->getRequestWithCookieParams($request);

        return $this->httpHandleService->handle($request, $this->portalNodeKey);
    }

    private function getRequestWithCookieParams(ServerRequestInterface $request): ServerRequestInterface
    {
        $cookies = \explode(';', $request->getHeaderLine('Cookie'));
        $cookieParams = [];

        foreach ($cookies as $cookie) {
            [$cookieName, $cookieValue] = \explode('=', $cookie, 2);

            if (!\is_string($cookieName) || !\is_string($cookieValue)) {
                continue;
            }

            $cookieName = \trim($cookieName);
            $cookieValue = \trim($cookieValue);

            $cookieParams[$cookieName] = $cookieValue;
        }

        return $request->withCookieParams($cookieParams);
    }

    private function getRequestWithQueryParams(ServerRequestInterface $request): ServerRequestInterface
    {
        \parse_str($request->getUri()->getQuery(), $queryParams);

        return $request->withQueryParams($queryParams);
    }
}
