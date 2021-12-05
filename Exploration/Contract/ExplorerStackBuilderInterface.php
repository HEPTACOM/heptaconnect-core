<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

interface ExplorerStackBuilderInterface
{
    public function push(ExplorerContract $explorer): self;

    public function pushSource(): self;

    public function pushDecorators(): self;

    public function build(): ExplorerStackInterface;

    public function isEmpty(): bool;
}
