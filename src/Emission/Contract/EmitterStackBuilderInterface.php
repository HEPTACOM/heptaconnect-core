<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

interface EmitterStackBuilderInterface
{
    public function pushSource(): self;

    public function pushDecorators(): self;

    public function build(): EmitterStackInterface;

    public function isEmpty(): bool;
}
