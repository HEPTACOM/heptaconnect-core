<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Riverline\MultiPartParser\Converters\PSR7 as MultiPartParser;

final class HttpKernel implements HttpKernelInterface
{
    private PortalNodeKeyInterface $portalNodeKey;

    private HttpHandleServiceInterface $httpHandleService;

    private StreamFactoryInterface $streamFactory;

    private UploadedFileFactoryInterface $uploadedFileFactory;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        HttpHandleServiceInterface $httpHandleService,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    ) {
        $this->portalNodeKey = $portalNodeKey;
        $this->httpHandleService = $httpHandleService;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->getRequestWithQueryParams($request);
        $request = $this->getRequestWithCookieParams($request);
        $request = $this->getRequestWithParsedBodyAndUploadedFiles($request);

        return $this->httpHandleService->handle($request, $this->portalNodeKey);
    }

    private function getRequestWithCookieParams(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->getCookieParams() !== []) {
            return $request;
        }

        $cookies = \explode(';', $request->getHeaderLine('Cookie'));
        $cookieParams = [];

        foreach ($cookies as $cookie) {
            $cookieParts = \explode('=', $cookie, 2);

            if (\count($cookieParts) !== 2) {
                continue;
            }

            [$cookieName, $cookieValue] = $cookieParts;

            $cookieName = \trim($cookieName);
            $cookieValue = \trim($cookieValue);

            $cookieParams[$cookieName] = $cookieValue;
        }

        return $request->withCookieParams($cookieParams);
    }

    private function getRequestWithQueryParams(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->getQueryParams() !== []) {
            return $request;
        }

        \parse_str($request->getUri()->getQuery(), $queryParams);

        return $request->withQueryParams($queryParams);
    }

    private function getRequestWithParsedBodyAndUploadedFiles(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->getParsedBody() !== null || $request->getUploadedFiles() !== []) {
            return $request;
        }

        $contentType = $request->getHeaderLine('Content-Type');

        if (\str_starts_with($contentType, 'multipart/form-data')) {
            $document = MultiPartParser::convert($request);

            $parsedBodyIndex = 0;
            $parsedBody = [];

            $uploadedFilesIndex = 0;
            $uploadedFiles = [];

            foreach ($document->getParts() as $part) {
                $name = $part->getName();
                $body = $part->getBody();

                if ($part->isFile()) {
                    $fileName = $part->getFileName();
                    $mimeType = $part->getMimeType();

                    $uploadedFile = $this->uploadedFileFactory->createUploadedFile(
                        $this->streamFactory->createStream($body),
                        null,
                        \UPLOAD_ERR_OK,
                        $fileName,
                        $mimeType
                    );

                    $name ??= $uploadedFilesIndex++;

                    \parse_str(\sprintf('%s=1', $name), $result);
                    \array_walk_recursive($result, fn (&$value) => $value = $uploadedFile);
                    $uploadedFiles = \array_replace_recursive($uploadedFiles, $result);
                } else {
                    $name ??= $parsedBodyIndex++;

                    \parse_str(\sprintf('%s=1', $name), $result);
                    \array_walk_recursive($result, fn (&$value) => $value = $body);
                    $parsedBody = \array_replace_recursive($parsedBody, $result);
                }
            }

            if ($parsedBody !== []) {
                $request = $request->withParsedBody($parsedBody);
            }

            if ($uploadedFiles !== []) {
                $request = $request->withUploadedFiles($uploadedFiles);
            }
        } elseif (\str_starts_with($contentType, 'application/json')) {
            try {
                $parsedBody = \json_decode(
                    (string) $request->getBody(),
                    true,
                    512,
                    \JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $_) {
                $parsedBody = null;
            }

            if (\is_array($parsedBody)) {
                $request = $request->withParsedBody($parsedBody);
            }
        } elseif (\str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            \parse_str((string) $request->getBody(), $parsedBody);

            if (\is_array($parsedBody) && $parsedBody !== []) {
                $request = $request->withParsedBody($parsedBody);
            }
        }

        return $request;
    }
}
