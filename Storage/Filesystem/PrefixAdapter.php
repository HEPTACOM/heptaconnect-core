<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Filesystem;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

final class PrefixAdapter extends AbstractAdapter
{
    private AdapterInterface $decorated;

    public function __construct(AdapterInterface $decorated, string $prefix)
    {
        $this->decorated = $decorated;

        if ($prefix === '') {
            throw new \InvalidArgumentException('The prefix must not be empty.');
        }

        $this->setPathPrefix($this->normalizePrefix($prefix));
    }

    public function write($path, $contents, Config $config)
    {
        return $this->decorated->write($this->applyPathPrefix($path), $contents, $config);
    }

    public function writeStream($path, $resource, Config $config)
    {
        return $this->decorated->writeStream($this->applyPathPrefix($path), $resource, $config);
    }

    public function update($path, $contents, Config $config)
    {
        return $this->decorated->update($this->applyPathPrefix($path), $contents, $config);
    }

    public function updateStream($path, $resource, Config $config)
    {
        return $this->decorated->updateStream($this->applyPathPrefix($path), $resource, $config);
    }

    public function rename($path, $newpath)
    {
        return $this->decorated->rename($this->applyPathPrefix($path), $this->applyPathPrefix($newpath));
    }

    public function copy($path, $newpath)
    {
        return $this->decorated->copy($this->applyPathPrefix($path), $this->applyPathPrefix($newpath));
    }

    public function delete($path)
    {
        return $this->decorated->delete($this->applyPathPrefix($path));
    }

    public function deleteDir($dirname)
    {
        return $this->decorated->deleteDir($this->applyPathPrefix($dirname));
    }

    public function createDir($dirname, Config $config)
    {
        return $this->decorated->createDir($this->applyPathPrefix($dirname), $config);
    }

    public function setVisibility($path, $visibility)
    {
        return $this->decorated->setVisibility($this->applyPathPrefix($path), $visibility);
    }

    public function has($path)
    {
        return $this->decorated->has($this->applyPathPrefix($path));
    }

    public function read($path)
    {
        return $this->decorated->read($this->applyPathPrefix($path));
    }

    public function readStream($path)
    {
        return $this->decorated->readStream($this->applyPathPrefix($path));
    }

    public function listContents($directory = '', $recursive = false)
    {
        $original = $this->decorated->listContents($this->applyPathPrefix($directory), $recursive);
        $result = [];

        foreach ($original as $file) {
            $file['path'] = $this->removePathPrefix($file['path']);
            $result[] = $file;
        }

        return \array_filter($result);
    }

    public function getMetadata($path)
    {
        return $this->decorated->getMetadata($this->applyPathPrefix($path));
    }

    public function getSize($path)
    {
        return $this->decorated->getSize($this->applyPathPrefix($path));
    }

    public function getMimetype($path)
    {
        return $this->decorated->getMimetype($this->applyPathPrefix($path));
    }

    public function getTimestamp($path)
    {
        return $this->decorated->getTimestamp($this->applyPathPrefix($path));
    }

    public function getVisibility($path)
    {
        return $this->decorated->getVisibility($this->applyPathPrefix($path));
    }

    private function normalizePrefix(string $prefix): string
    {
        return \trim($prefix, '/') . '/';
    }
}
