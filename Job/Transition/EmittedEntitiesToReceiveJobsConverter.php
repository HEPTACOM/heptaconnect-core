<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Transition;

use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\EmittedEntitiesToJobsConverterInterface;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Psr\Log\LoggerInterface;

final class EmittedEntitiesToReceiveJobsConverter implements EmittedEntitiesToJobsConverterInterface
{
    public function __construct(
        private ReceptionRouteListActionInterface $receptionRouteListAction,
        private LoggerInterface $logger
    ) {
    }

    public function convert(PortalNodeKeyInterface $portalNodeKey, DatasetEntityCollection $entities): JobCollection
    {
        $result = new JobCollection();
        $entityTypes = [];

        foreach ($entities->groupByType() as $entityTypeString => $typedEntities) {
            $entityTypes = $entityTypeString;
            $entityType = new EntityType($entityTypeString);
            $routeListCriteria = new ReceptionRouteListCriteria($portalNodeKey, $entityType);
            /** @var ReceptionRouteListResult[] $receptionRoutes */
            $receptionRoutes = \iterable_to_array($this->receptionRouteListAction->list($routeListCriteria));

            foreach ($receptionRoutes as $receptionRoute) {
                $result->push($typedEntities->map(
                    static fn (DatasetEntityContract $entity): Reception => new Reception(
                        new MappingComponentStruct($portalNodeKey, $entityType, (string) $entity->getPrimaryKey()),
                        $receptionRoute->getRouteKey(),
                        $entity
                    )
                ));
            }
        }

        if ($result->count() < 1) {
            $this->logger->warning('Entity conversion to receive jobs created no jobs', [
                'entityTypes' => $entityTypes,
                'portalNode' => $portalNodeKey,
                'code' => 1661091900,
            ]);
        }

        return $result;
    }
}
