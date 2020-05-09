<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

class FooBarEmitter implements EmitterInterface
{
    private int $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function emit(MappingCollection $mappings, EmitContextInterface $context): iterable
    {
        for ($c = 0; $c < $this->count; ++$c) {
            yield new MappedDatasetEntityStruct(new MappingStruct(), new FooBarEntity());
        }
    }

    public function supports(): array
    {
        return [
            FooBarEntity::class,
        ];
    }
}
