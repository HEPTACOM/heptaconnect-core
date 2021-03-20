<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;

class ThrowEmitter extends EmitterContract
{
    public function emit(MappingCollection $mappings, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        throw new \RuntimeException();
    }

    public function supports(): string
    {
        return FooBarEntity::class;
    }
}
