<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddDefault;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteAddUiDefaultProviderInterface;

final class RouteAddUiDefault implements RouteAddUiDefaultProviderInterface
{
    public function getDefault(): RouteAddDefault
    {
        return new RouteAddDefault(RouteCapability::ALL);
    }
}
