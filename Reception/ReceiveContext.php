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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ReceiveContext extends AbstractPortalNodeContext implements ReceiveContextInterface
{
    private EventDispatcherInterface $eventDispatcher;

    private PostProcessorDataBag $postProcessingBag;

    /**
     * @param EventSubscriberInterface[] $postProcessors
     */
    public function __construct(
        PortalNodeContainerFacadeContract $containerFacade,
        ?array $configuration,
        private readonly EntityStatusContract $entityStatus,
        private readonly array $postProcessors
    ) {
        parent::__construct($containerFacade, $configuration);
        $this->postProcessingBag = new PostProcessorDataBag();
        $this->initializeEventDispatcher();
    }

    public function __clone()
    {
        $this->postProcessingBag = new PostProcessorDataBag();
        $this->initializeEventDispatcher();
    }

    #[\Override]
    public function getPostProcessingBag(): PostProcessorDataBag
    {
        return $this->postProcessingBag;
    }

    #[\Override]
    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }

    #[\Override]
    public function markAsFailed(DatasetEntityContract $entity, \Throwable $throwable): void
    {
        $this->getPostProcessingBag()->add(new MarkAsFailedData($entity, $throwable));
    }

    #[\Override]
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
