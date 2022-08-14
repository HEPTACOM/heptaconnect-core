<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer\Contract;

use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationCollection;

interface PackageConfigurationLoaderInterface
{
    /**
     * Get all relevant packages and their relevant information.
     */
    public function getPackageConfigurations(): PackageConfigurationCollection;
}
