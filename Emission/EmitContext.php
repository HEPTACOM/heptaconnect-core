<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Psr\Container\ContainerInterface;

class EmitContext extends AbstractPortalNodeContext implements EmitContextInterface
{
    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private bool $directEmission;

    public function __construct(
        ContainerInterface $container,
        ?array $configuration,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository,
        bool $directEmission
    ) {
        parent::__construct($container, $configuration);

        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->directEmission = $directEmission;
    }

    public function isDirectEmission(): bool
    {
        return $this->directEmission;
    }

    public function markAsFailed(string $externalId, string $entityType, \Throwable $throwable): void
    {
        $mappingNodeKeys = $this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalIds(
            $entityType,
            $this->getPortalNodeKey(),
            [$externalId]
        );

        foreach ($mappingNodeKeys as $mappingNodeKey) {
            $this->mappingService->addException(
                $this->getPortalNodeKey(),
                $mappingNodeKey,
                $throwable
            );
        }
    }
}
