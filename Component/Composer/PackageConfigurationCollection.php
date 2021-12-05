<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Heptacom\HeptaConnect\Dataset\Base\Support\AbstractCollection;

/**
 * @extends \Heptacom\HeptaConnect\Dataset\Base\Support\AbstractCollection<\Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration>
 */
class PackageConfigurationCollection extends AbstractCollection
{
    protected function isValidItem($item): bool
    {
        /* @phpstan-ignore-next-line treatPhpDocTypesAsCertain checks soft check but this is the hard check */
        return $item instanceof PackageConfiguration;
    }
}
