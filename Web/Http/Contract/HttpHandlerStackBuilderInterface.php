<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;

interface HttpHandlerStackBuilderInterface
{
    public function push(HttpHandlerContract $httpHandler): self;

    public function pushSource(): self;

    public function pushDecorators(): self;

    public function build(): HttpHandlerStackInterface;

    public function isEmpty(): bool;
}
