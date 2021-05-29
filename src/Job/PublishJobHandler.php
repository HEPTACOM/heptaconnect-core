<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;

class PublishJobHandler
{
    private MappingServiceInterface $mappingService;

    private EmitServiceInterface $emitService;

    public function __construct(MappingServiceInterface $mappingService, EmitServiceInterface $emitService)
    {
        $this->mappingService = $mappingService;
        $this->emitService = $emitService;
    }

    public function publish(MappingComponentStructContract $mapping): void
    {
        $mapping = $this->mappingService->get(
            $mapping->getDatasetEntityClassName(),
            $mapping->getPortalNodeKey(),
            $mapping->getExternalId()
        );

        $this->emitService->emit(new TypedMappingCollection($mapping->getDatasetEntityClassName(), [$mapping]));
    }
}