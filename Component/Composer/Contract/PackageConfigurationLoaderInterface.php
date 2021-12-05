<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer\Contract;

use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationCollection;

interface PackageConfigurationLoaderInterface
{
    public function getPackageConfigurations(): PackageConfigurationCollection;
}
