<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityError\IdentityErrorCreateActionInterface;
use Psr\Container\ContainerInterface;

class EmitContext extends AbstractPortalNodeContext implements EmitContextInterface
{
    private IdentityErrorCreateActionInterface $identityErrorCreateAction;

    private bool $directEmission;

    public function __construct(
        ContainerInterface $container,
        ?array $configuration,
        IdentityErrorCreateActionInterface $identityErrorCreateAction,
        bool $directEmission
    ) {
        parent::__construct($container, $configuration);

        $this->identityErrorCreateAction = $identityErrorCreateAction;
        $this->directEmission = $directEmission;
    }

    public function isDirectEmission(): bool
    {
        return $this->directEmission;
    }

    public function markAsFailed(string $externalId, string $entityType, \Throwable $throwable): void
    {
        $mappingComponent = new MappingComponentStruct($this->getPortalNodeKey(), $entityType, $externalId);
        $payload = new IdentityErrorCreatePayloads([new IdentityErrorCreatePayload($mappingComponent, $throwable)]);

        $this->identityErrorCreateAction->create($payload);
    }
}
