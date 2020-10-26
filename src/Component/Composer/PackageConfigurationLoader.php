<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Composer\Autoload\ClassMapGenerator;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\PackageInterface;
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
            $cacheItem = $this->cache->getItem(str_replace('\\', '-', self::class) . '-' . $cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } else {
            $cacheItem = null;
        }

        $factory = new Factory();
        $composer = $factory->createComposer(
            new NullIO(), $this->composerJson,
            false,
            \is_null($this->composerJson) ? null : \dirname($this->composerJson)
        );
        $result = new PackageConfigurationCollection();

        foreach ($this->iteratePackages($composer) as $packageInstance) {
            $config = new PackageConfiguration();
            $heptaconnectKeywords = \array_filter(
                (array) ($package['keywords'] ?? []),
                fn (string $k): bool => str_starts_with($k, 'heptaconnect-')
            );

            if (empty($heptaconnectKeywords)) {
                continue;
            }

            $config->setName((string) $package['name']);
            $config->setTags(new StringCollection($heptaconnectKeywords));

            $extra = (array) ($package['extra'] ?? []);
            $heptaconnect = (array) ($extra['heptaconnect'] ?? []);

            if (\count($heptaconnect) > 0) {
                /* @var array<array-key, string> $keywords */
                $config->setConfiguration($heptaconnect);
            }

            $classLoader = $composer->getAutoloadGenerator()->createLoader((array) ($package['autoload'] ?? []));

            foreach ($classLoader->getPrefixesPsr4() as $namespace => $dirs) {
                foreach ($dirs as $dir) {
                    $installPath = $composer->getInstallationManager()->getInstallPath($packageInstance);
                    $classMap = ClassMapGenerator::createMap($installPath.\DIRECTORY_SEPARATOR.$dir);

                    foreach ($classMap as $class => $file) {
                        $config->getAutoloadedFiles()->addClass($class, $file);
                    }
                }
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
        if (\is_file($this->composerJson)) {
            return hash_file('md5', $this->composerJson);
        } elseif (\is_string($this->composerJson)) {
            return hash('md5', $this->composerJson);
        } else {
            return null;
        }
    }

    /**
     * @return iterable<\Composer\Package\PackageInterface>
     */
    private function iteratePackages(Composer $composer): iterable
    {
        if ($composer->getLocker()->isLocked()) {
            $packageLockData = (array) ($composer->getLocker()->getLockData()['packages'] ?? []);
            $packageLockData = \array_filter($packageLockData, 'is_array');

            /** @var array $package */
            foreach ($packageLockData as $package) {
                $packageInstance = $composer->getLocker()->getLockedRepository()->findPackage($package['name'], $package['version']);

                if (!$packageInstance instanceof PackageInterface) {
                    continue;
                }

                yield $packageInstance;
            }
        }
    }
}
