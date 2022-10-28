<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\File\Filesystem;

use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Contract\FilesystemInterface;
use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Exception\UnexpectedFormatOfUriException;
use Psr\Http\Message\UriFactoryInterface;

final class Filesystem implements FilesystemInterface
{
    private UriFactoryInterface $uriFactory;

    private string $scheme;

    public function __construct(UriFactoryInterface $uriFactory, string $scheme)
    {
        $this->uriFactory = $uriFactory;
        $this->scheme = $scheme;
    }

    public function toStoragePath(string $path): string
    {
        try {
            $uri = $this->uriFactory->createUri($path);
        } catch (\Throwable $throwable) {
            throw new UnexpectedFormatOfUriException($path, 'path', 1666942800, $throwable);
        }

        if ($uri->getHost() !== '') {
            $uri->withPath($uri->getHost() . '/' . $uri->getPath())->withHost('');
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

        return (string) $uri->withScheme($this->scheme);
    }

    public function fromStoragePath(string $uri): string
    {
        try {
            $parsed = $this->uriFactory->createUri($uri);
        } catch (\Throwable $throwable) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942810, $throwable);
        }

        if ($parsed->getScheme() !== $this->scheme) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942811);
        }

        if ($parsed->getHost() !== '') {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942812);
        }

        if ($parsed->getPort() !== null) {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942813);
        }

        if ($parsed->getQuery() !== '') {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942814);
        }

        if ($parsed->getFragment() !== '') {
            throw new UnexpectedFormatOfUriException($uri, 'scheme:path', 1666942815);
        }

        return (string) $parsed->withScheme('');
    }
}
