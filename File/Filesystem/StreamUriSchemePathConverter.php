<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Filesystem;

use Heptacom\HeptaConnect\Core\File\Filesystem\Contract\StreamUriSchemePathConverterInterface;
use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Exception\UnexpectedFormatOfUriException;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final class StreamUriSchemePathConverter implements StreamUriSchemePathConverterInterface
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private string $scheme
    ) {
    }

    public function convertToUri(string $path): string
    {
        try {
            $uri = $this->uriFactory->createUri($path);
        } catch (\Throwable $throwable) {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942800, $throwable);
        }

        if ($uri->getHost() !== '') {
            $uri = $uri->withPath($uri->getHost() . '/' . $uri->getPath())->withHost('');
        }

        if ($uri->getHost() === '') {
            $urlParts = \explode('/', \ltrim($uri->getPath(), '/'), 2);
            $uri = $uri->withPath($urlParts[1] ?? '')->withHost($urlParts[0]);
        }

        if ($uri->getScheme() !== '') {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942801);
        }

        if ($uri->getPort() !== null) {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942802);
        }

        if ($uri->getQuery() !== '') {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942803);
        }

        if ($uri->getFragment() !== '') {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942804);
        }

        $result = (string) $uri->withScheme($this->scheme);

        if (!\str_contains($result, '://')) {
            return \rtrim($result, ':/') . '://';
        }

        return $result;
    }

    public function convertToPath(string $uri): string
    {
        try {
            $parsed = $this->uriFactory->createUri($uri);
        } catch (\Throwable $throwable) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme://path', 1666942810, $throwable);
        }

        if ($parsed->getScheme() !== $this->scheme) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme://path', 1666942811);
        }

        if ($parsed->getPort() !== null) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme://path', 1666942812);
        }

        if ($parsed->getQuery() !== '') {
            throw new UnexpectedFormatOfUriException($uri, 'scheme://path', 1666942813);
        }

        if ($parsed->getFragment() !== '') {
            throw new UnexpectedFormatOfUriException($uri, 'scheme://path', 1666942814);
        }

        $path = $parsed->getPath();

        return $path === '' ? $parsed->getAuthority() : ($parsed->getAuthority() . '/' . \ltrim($path, '/'));
    }
}
