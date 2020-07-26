<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Composer\Factory;
use Composer\IO\NullIO;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;

class PackageConfigurationLoader implements Contract\PackageConfigurationLoaderInterface
{
    private ?string $composerJson;

    public function __construct(?string $composerJson)
    {
        $this->composerJson = $composerJson;
    }

    public function getPackageConfigurations(): PackageConfigurationCollection
    {
        $factory = Factory::create(new NullIO(), $this->composerJson);
        $result = new PackageConfigurationCollection();

        if ($factory->getLocker()->isLocked()) {
            $packageLockData = (array) ($factory->getLocker()->getLockData()['packages'] ?? []);
            $packageLockData = \array_filter($packageLockData, 'is_array');

            /** @var array $package */
            foreach ($packageLockData as $package) {
                $extra = (array) ($package['extra'] ?? []);
                $heptaconnect = (array) ($extra['heptaconnect'] ?? []);

                if (\count($heptaconnect) > 0) {
                    $config = new PackageConfiguration();
                    $config->setName((string) $package['name']);
                    /** @var array<array-key, string> $keywords */
                    $keywords = \array_filter((array) ($package['keywords'] ?? []), fn (string $k): bool => str_starts_with($k, 'heptaconnect-'));
                    $config->setTags(new StringCollection($keywords));
                    $config->setConfiguration($heptaconnect);
                    $result->push([$config]);
                }
            }
        }

        return $result;
    }
}
