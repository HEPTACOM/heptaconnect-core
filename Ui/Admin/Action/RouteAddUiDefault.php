<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Route\RouteAdd\RouteAddDefault;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Route\RouteAddUiDefaultProviderInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class RouteAddUiDefault implements RouteAddUiDefaultProviderInterface
{
    public static function class(): UiActionType
    {
        return new UiActionType(RouteAddUiDefaultProviderInterface::class);
    }

    public function getDefault(UiActionContextInterface $context): RouteAddDefault
    {
        return new RouteAddDefault(RouteCapability::ALL);
    }
}
