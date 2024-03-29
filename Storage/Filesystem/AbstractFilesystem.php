<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\PluginInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractFilesystem implements FilesystemInterface
{
    public function __construct(
        protected FilesystemInterface $filesystem
    ) {
        if (!\method_exists($this->filesystem, 'getConfig')) {
            throw new \UnexpectedValueException('Filesystem does not expose config');
        }

        if (!\method_exists($this->filesystem, 'getAdapter')) {
            throw new \UnexpectedValueException('Filesystem does not expose adapter');
        }
    }

    public function has($path): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->has($path);
    }

    public function read($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->read($path);
    }

    public function readStream($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->readStream($path);
    }

    public function listContents($directory = '', $recursive = false): array
    {
        $directory = $this->preparePath($directory);
        /** @var array{path: string, dirname: string}[] $contents */
        $contents = $this->filesystem->listContents($directory, $recursive);

        return \array_map([$this, 'stripPathFromContent'], $contents);
    }

    public function getMetadata($path)
    {
        $path = $this->preparePath($path);

        /** @var array{path: string}|array{dirname: string, path: string}|false $meta */
        $meta = $this->filesystem->getMetadata($path);

        if (!\is_array($meta)) {
            return $meta;
        }

        if (\array_key_exists('path', $meta)) {
            $meta['path'] = $this->stripPath($meta['path']);
        }

        if (\array_key_exists('dirname', $meta)) {
            $meta['dirname'] = $this->stripPath($meta['dirname']);
        }

        return $meta;
    }

    public function getSize($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->getSize($path);
    }

    public function getMimetype($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->getMimetype($path);
    }

    public function getTimestamp($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->getTimestamp($path);
    }

    public function getVisibility($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->getVisibility($path);
    }

    public function write($path, $contents, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->write($path, $contents, $config);
    }

    public function writeStream($path, $resource, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->writeStream($path, $resource, $config);
    }

    public function update($path, $contents, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->update($path, $contents, $config);
    }

    public function updateStream($path, $resource, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->updateStream($path, $resource, $config);
    }

    public function rename($path, $newpath): bool
    {
        $path = $this->preparePath($path);
        $newpath = $this->preparePath($newpath);

        return $this->filesystem->rename($path, $newpath);
    }

    public function copy($path, $newpath): bool
    {
        $path = $this->preparePath($path);
        $newpath = $this->preparePath($newpath);

        return $this->filesystem->copy($path, $newpath);
    }

    public function delete($path): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->delete($path);
    }

    public function deleteDir($dirname): bool
    {
        $dirname = $this->preparePath($dirname);

        return $this->filesystem->deleteDir($dirname);
    }

    public function createDir($dirname, array $config = []): bool
    {
        $dirname = $this->preparePath($dirname);

        return $this->filesystem->createDir($dirname, $config);
    }

    public function setVisibility($path, $visibility): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->setVisibility($path, $visibility);
    }

    public function put($path, $contents, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->put($path, $contents, $config);
    }

    public function putStream($path, $resource, array $config = []): bool
    {
        $path = $this->preparePath($path);

        return $this->filesystem->putStream($path, $resource, $config);
    }

    public function readAndDelete($path)
    {
        $path = $this->preparePath($path);

        return $this->filesystem->readAndDelete($path);
    }

    public function get($path, ?Handler $handler = null): Handler
    {
        $path = $this->preparePath($path);

        return $this->filesystem->get($path, $handler);
    }

    /**
     * @return never
     */
    public function addPlugin(PluginInterface $plugin)
    {
        throw new \RuntimeException('Filesystem plugins are not allowed in abstract filesystems.');
    }

    /**
     * Get the Adapter.
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->filesystem->getAdapter();
    }

    public function getConfig(): Config
    {
        return $this->filesystem->getConfig();
    }

    /**
     * Modify the path before it will be passed to the filesystem
     */
    abstract public function preparePath(string $path): string;

    /**
     * Remove the modified parts from the filesystem
     */
    abstract public function stripPath(string $path): string;

    /**
     * @param array{dirname: string, path: string} $info
     *
     * @return array{dirname: string, path: string}
     */
    private function stripPathFromContent(array $info)
    {
        $info['dirname'] = $this->stripPath($info['dirname']);
        $info['path'] = $this->stripPath($info['path']);

        return $info;
    }
}
