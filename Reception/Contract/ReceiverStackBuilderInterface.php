<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

interface ReceiverStackBuilderInterface
{
    public function push(ReceiverContract $receiver): self;

    public function pushSource(): self;

    public function pushDecorators(): self;

    public function build(): ReceiverStackInterface;

    public function isEmpty(): bool;
}
