<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

class FooBarEmitter implements EmitterInterface
{
    private int $count;

    private PortalNodeKeyInterface $portalNodeKey;

    private MappingNodeKeyInterface $mappingNodeKey;

    public function __construct(
        int $count,
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey
    ) {
        $this->count = $count;
        $this->portalNodeKey = $portalNodeKey;
        $this->mappingNodeKey = $mappingNodeKey;
    }

    public function emit(MappingCollection $mappings, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        for ($c = 0; $c < $this->count; ++$c) {
            yield new MappedDatasetEntityStruct(
                new MappingStruct($this->portalNodeKey, $this->mappingNodeKey),
                new FooBarEntity()
            );
        }

        yield from $stack->next($mappings, $context);
    }

    public function supports(): array
    {
        return [
            FooBarEntity::class,
        ];
    }
}
