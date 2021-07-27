<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Core\Reception\Support\PostProcessorDataBag;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReceiveContext extends AbstractPortalNodeContext implements ReceiveContextInterface
{
    private EntityStatusContract $entityStatus;

    private EventDispatcherInterface $eventDispatcher;

    private PostProcessorDataBag $postProcessingBag;

    public function __construct(
        ContainerInterface $container,
        ?array $configuration,
        EntityStatusContract $entityStatus,
        array $postProcessors
    ) {
        parent::__construct($container, $configuration);
        $this->entityStatus = $entityStatus;
        $this->postProcessingBag = new PostProcessorDataBag();
        $this->eventDispatcher = new EventDispatcher();
        foreach ($postProcessors as $postProcessor) {
            $this->eventDispatcher->addSubscriber($postProcessor);
        }
    }

    public function getPostProcessingBag(): PostProcessorDataBag
    {
        return $this->postProcessingBag;
    }

    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
