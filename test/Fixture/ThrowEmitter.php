<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

class ThrowEmitter implements EmitterInterface
{
    public function emit(MappingCollection $mappings, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        throw new \RuntimeException();
    }

    public function supports(): array
    {
        return [
            FooBarEntity::class,
        ];
    }
}
