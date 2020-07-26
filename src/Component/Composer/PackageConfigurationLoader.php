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
                    $config->setTags(new StringCollection($this->getHeptaconnectKeywords((array) ($package['keywords'] ?? []))));
                    $config->setConfiguration($heptaconnect);
                    $result->push([$config]);
                }
            }
        }

        return $result;
    }

    /**
     * @return array<array-key, string>
     */
    private function getHeptaconnectKeywords(array $keywords): array
    {
        /* @var array<array-key, string> */
        return \array_filter($keywords, fn (string $k): bool => str_starts_with($k, 'heptaconnect-'));
    }
}
