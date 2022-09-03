<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Type;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;

class Reception extends AbstractJobType
{
    public const ROUTE_KEY = 'routeKey';

    public const ENTITY = 'entity';

    protected RouteKeyInterface $routeKey;

    protected DatasetEntityContract $entity;

    public function __construct(
        MappingComponentStructContract $mapping,
        RouteKeyInterface $routeKey,
        DatasetEntityContract $entity
    ) {
        parent::__construct($mapping);

        $this->routeKey = $routeKey;
        $this->entity = $entity;
    }

    public function getPayload(): ?array
    {
        return [
            self::ROUTE_KEY => $this->getRouteKey(),
            self::ENTITY => $this->getEntity(),
        ];
    }

    public function getRouteKey(): RouteKeyInterface
    {
        return $this->routeKey;
    }

    public function getEntity(): DatasetEntityContract
    {
        return $this->entity;
    }
}
