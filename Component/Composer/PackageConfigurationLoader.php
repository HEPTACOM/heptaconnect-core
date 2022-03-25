<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Composer\Autoload\ClassMapGenerator;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class PackageConfigurationLoader implements Contract\PackageConfigurationLoaderInterface
{
    private ?string $composerJson;

    private CacheItemPoolInterface $cache;

    public function __construct(?string $composerJson, CacheItemPoolInterface $cache)
    {
        $this->composerJson = $composerJson;
        $this->cache = $cache;
    }

    public function getPackageConfigurations(): PackageConfigurationCollection
    {
        $cacheKey = $this->getCacheKey();

        if (\is_string($cacheKey)) {
            $cacheItem = $this->cache->getItem(\str_replace('\\', '-', self::class) . '-' . $cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } else {
            $cacheItem = null;
        }

        $factory = new Factory();
        $workingDir = null;

        if ($this->composerJson !== null) {
            $workingDir = \dirname($this->composerJson);

            if (!@\is_dir($workingDir . \DIRECTORY_SEPARATOR . 'vendor')) {
                $workingDir = null;
            }
        }

        $composer = $factory->createComposer(new NullIO(), $this->composerJson, false, $workingDir);
        $result = new PackageConfigurationCollection();

        if ($workingDir === null) {
            $cwd = \getcwd();

            if (\is_string($cwd)) {
                $workingDir = $cwd;
            }
        }

        foreach ($this->iteratePackages($composer) as $packageInstance) {
            $config = new PackageConfiguration();
            $heptaconnectKeywords = \array_filter(
                $packageInstance->getKeywords() ?? [],
                fn (string $k): bool => \str_starts_with($k, 'heptaconnect-')
            );

            if ($heptaconnectKeywords === []) {
                continue;
            }

            $config->setName($packageInstance->getName());
            $config->setTags(new StringCollection($heptaconnectKeywords));

            $extra = $packageInstance->getExtra() ?? [];
            $heptaconnect = (array) ($extra['heptaconnect'] ?? []);

            if ($heptaconnect !== []) {
                /* @var array<array-key, string> $keywords */
                $config->setConfiguration($heptaconnect);
            }

            foreach ($this->iterateClassMaps($composer, $packageInstance, $workingDir) as $class => $file) {
                $config->getAutoloadedFiles()->addClass($class, $file);
            }

            $result->push([$config]);
        }

        if ($cacheItem instanceof CacheItemInterface) {
            $cacheItem->set($result);
            $this->cache->save($cacheItem);
        }

        return $result;
    }

    private function getCacheKey(): ?string
    {
        $filename = $this->composerJson;

        if (!\is_string($filename)) {
            return null;
        }

        if (\is_file($filename)) {
            $result = \hash_file('md5', $filename);

            if (\is_string($result)) {
                return $result;
            }
        }

        /** @var string|bool $result */
        $result = \hash('md5', $filename);

        return \is_string($result) ? $result : null;
    }

    /**
     * @return iterable<CompletePackageInterface>
     */
    private function iteratePackages(Composer $composer): iterable
    {
        $locker = $composer->getLocker();

        if ($locker instanceof Locker && $locker->isLocked()) {
            $packageLockData = (array) ($locker->getLockData()['packages'] ?? []);
            $packageLockData = \array_filter($packageLockData, 'is_array');

            foreach ($packageLockData as $package) {
                $packageInstance = $locker->getLockedRepository()->findPackage($package['name'], $package['version']);

                if (!$packageInstance instanceof CompletePackageInterface) {
                    continue;
                }

                yield $packageInstance;
            }
        }

        yield $composer->getPackage();
    }

    /**
     * @psalm-return iterable<class-string, string>
     */
    private function iterateClassMaps(
        Composer $composer,
        CompletePackageInterface $package,
        ?string $workingDir
    ): iterable {
        $classLoader = $composer->getAutoloadGenerator()->createLoader($package->getAutoload() ?? []);
        $installPath = $composer->getInstallationManager()->getInstallPath($package);

        foreach ($classLoader->getPrefixesPsr4() as $namespace => $dirs) {
            foreach ($dirs as $dir) {
                if (\is_dir($absolute = $installPath . \DIRECTORY_SEPARATOR . $dir)) {
                    yield from ClassMapGenerator::createMap($absolute);
                }
                // TODO log. This is a weird case

                if ($package instanceof RootPackageInterface
                    && $workingDir !== null
                    && \is_dir($absolute = $workingDir . \DIRECTORY_SEPARATOR . $dir)) {
                    yield from ClassMapGenerator::createMap($absolute);
                }
            }
        }
    }
}
