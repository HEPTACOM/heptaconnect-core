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

final class PackageConfigurationLoader implements Contract\PackageConfigurationLoaderInterface
{
    public function __construct(
        private ?string $composerJson,
        private CacheItemPoolInterface $cache
    ) {
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

        $result = $this->getPackageConfigurationUncached();

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

            $localRepository = $composer->getRepositoryManager()->getLocalRepository();

            if ($localRepository->getDevMode() ?? false) {
                $packageDevLockData = (array) ($locker->getLockData()['packages-dev'] ?? []);
                $packageDevLockData = \array_filter($packageDevLockData, 'is_array');

                foreach ($packageDevLockData as $package) {
                    $packageInstance = $locker->getLockedRepository(true)->findPackage($package['name'], $package['version']);

                    if (!$packageInstance instanceof CompletePackageInterface) {
                        continue;
                    }

                    yield $packageInstance;
                }
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
        $classLoader = $composer->getAutoloadGenerator()->createLoader($package->getAutoload());
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

    private function getPackageConfigurationUncached(): PackageConfigurationCollection
    {
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

        \assert(\is_string($workingDir));

        foreach ($this->iteratePackages($composer) as $packageInstance) {
            /** @var array|null $keywords */
            $keywords = $packageInstance->getKeywords();
            /** @var array<int, string> $heptaconnectKeywords */
            $heptaconnectKeywords = \array_values(\array_filter(
                $keywords ?? [],
                static fn (string $keyword): bool => \str_starts_with($keyword, 'heptaconnect-')
            ));

            if ($heptaconnectKeywords === []) {
                continue;
            }

            $result->push([
                $this->getConfigFromPackage($packageInstance, $heptaconnectKeywords, $composer, $workingDir),
            ]);
        }

        return $result;
    }

    /**
     * @param array<int, string> $heptaconnectKeywords
     */
    private function getConfigFromPackage(
        CompletePackageInterface $packageInstance,
        array $heptaconnectKeywords,
        Composer $composer,
        string $workingDir
    ): PackageConfiguration {
        $config = new PackageConfiguration();
        $config->setName($packageInstance->getName());
        $config->setTags(new StringCollection($heptaconnectKeywords));

        $extra = $packageInstance->getExtra();
        $heptaconnect = (array) ($extra['heptaconnect'] ?? []);

        if ($heptaconnect !== []) {
            /* @var array<array-key, string> $keywords */
            $config->setConfiguration($heptaconnect);
        }

        foreach ($this->iterateClassMaps($composer, $packageInstance, $workingDir) as $class => $file) {
            $config->getAutoloadedFiles()->addClass($class, $file);
        }

        return $config;
    }
}
