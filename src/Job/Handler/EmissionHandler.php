<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;

class EmissionHandler
{
    private EmitServiceInterface $emitService;

    public function __construct(EmitServiceInterface $emitService)
    {
        $this->emitService = $emitService;
    }

    public function triggerEmission(MappingComponentStructContract $mapping): bool
    {
        $this->emitService->emit(new TypedMappingComponentCollection(
            $mapping->getDatasetEntityClassName(),
            [$mapping]
        ));

        return true;
    }
}
