<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Heptacom\HeptaConnect\Dataset\Base\Support\AbstractCollection;

/**
 * @extends AbstractCollection<PackageConfiguration>
 */
class PackageConfigurationCollection extends AbstractCollection
{
    protected function isValidItem(mixed $item): bool
    {
        return $item instanceof PackageConfiguration;
    }
}
