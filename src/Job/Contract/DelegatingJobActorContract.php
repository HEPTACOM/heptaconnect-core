<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

abstract class DelegatingJobActorContract
{
    abstract public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): bool;
}
