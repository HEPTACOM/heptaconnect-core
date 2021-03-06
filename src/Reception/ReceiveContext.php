<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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

    public function markAsFailed(DatasetEntityContract $entity, \Throwable $throwable): void
    {
        $mapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

        if ($mapping instanceof MappingInterface) {
            $this->mappingService->addException(
                $this->getPortalNodeKey(),
                $mapping->getMappingNodeKey(),
                $throwable
            );
        } else {
            $logger = $this->getContainer()->get(LoggerInterface::class);

            if ($logger instanceof LoggerInterface) {
                $logger->error(
                    'ReceiveContext: The reception of an unmappable entity failed. Exception: '.$throwable->getMessage()
                );
            }
        }
    }
}
