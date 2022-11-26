<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\PluggableTrait;

class PrefixFilesystem extends AbstractFilesystem
{
    use PluggableTrait;

    private string $prefix;

    public function __construct(FilesystemInterface $filesystem, string $prefix)
    {
        parent::__construct($filesystem);

        if ($prefix === '') {
            throw new \InvalidArgumentException('The prefix must not be empty.');
        }

        $this->prefix = $this->normalizePrefix($prefix);
    }

    public function stripPath(string $path): string
    {
        $prefix = \rtrim($this->prefix, '/');
        /** @var string $path */
        $path = \preg_replace('#^' . \preg_quote($prefix, '#') . '#', '', $path);
        $path = \ltrim($path, '/');

        return $path;
    }

    public function preparePath(string $path): string
    {
        return $this->prefix . $path;
    }

    public function getAdapter(): AdapterInterface
    {
        return new PrefixAdapter($this->filesystem->getAdapter(), $this->prefix);
    }

    private function normalizePrefix(string $prefix): string
    {
        return \trim($prefix, '/') . '/';
    }
}
