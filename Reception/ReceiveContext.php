<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeContainerFacadeContract;
use Heptacom\HeptaConnect\Core\Reception\PostProcessing\MarkAsFailedData;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Support\PostProcessorDataBag;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ReceiveContext extends AbstractPortalNodeContext implements ReceiveContextInterface
{
    private EntityStatusContract $entityStatus;

    private EventDispatcherInterface $eventDispatcher;

    private PostProcessorDataBag $postProcessingBag;

    private array $postProcessors;

    public function __construct(
        PortalNodeContainerFacadeContract $containerFacade,
        ?array $configuration,
        EntityStatusContract $entityStatus,
        array $postProcessors
    ) {
        parent::__construct($containerFacade, $configuration);
        $this->entityStatus = $entityStatus;
        $this->postProcessors = $postProcessors;
        $this->postProcessingBag = new PostProcessorDataBag();
        $this->initializeEventDispatcher();
    }

    public function __clone()
    {
        $this->postProcessingBag = new PostProcessorDataBag();
        $this->initializeEventDispatcher();
    }

    public function getPostProcessingBag(): PostProcessorDataBag
    {
        return $this->postProcessingBag;
    }

    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }

    public function markAsFailed(DatasetEntityContract $entity, \Throwable $throwable): void
    {
        $this->getPostProcessingBag()->add(new MarkAsFailedData($entity, $throwable));
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    private function initializeEventDispatcher(): void
    {
        $this->eventDispatcher = new EventDispatcher();

        foreach ($this->postProcessors as $postProcessor) {
            $this->eventDispatcher->addSubscriber($postProcessor);
        }
    }
}
