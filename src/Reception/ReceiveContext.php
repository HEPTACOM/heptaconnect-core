<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;
use Psr\Container\ContainerInterface;

class ReceiveContext extends AbstractPortalNodeContext implements ReceiveContextInterface
{
    private EntityStatusContract $entityStatus;

    private MappingServiceInterface $mappingService;

    public function __construct(
        ContainerInterface $container,
        ?array $configuration,
        MappingServiceInterface $mappingService,
        EntityStatusContract $entityStatus
    ) {
        parent::__construct($container, $configuration);
        $this->entityStatus = $entityStatus;
        $this->mappingService = $mappingService;
    }

    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }

    public function markAsFailed(MappingNodeKeyInterface $mappingNodeKey, \Throwable $throwable): void
    {
        $this->mappingService->addException(
            $this->getPortalNodeKey(),
            $mappingNodeKey,
            $throwable
        );
    }
}
