<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Log\LoggerInterface;

class MarkAsFailedPostProcessor extends PostProcessorContract
{

    private MappingServiceInterface $mappingService;

    public function __construct(MappingServiceInterface $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function handle(PostReceptionEvent $event): void
    {
        $markAsFailedData = iterable_map(
            $event->getContext()->getPostProcessingBag()->of(MarkAsFailedData::class),
            static fn (MarkAsFailedData $data) => $data
        );

        /** @var MarkAsFailedData $data */
        foreach ($markAsFailedData as $data) {
            $mapping = $data->getEntity()->getAttachment(PrimaryKeySharingMappingStruct::class);

            if ($mapping instanceof MappingInterface) {
                $this->mappingService->addException(
                    $event->getContext()->getPortalNodeKey(),
                    $mapping->getMappingNodeKey(),
                    $data->getThrowable()
                );
            } else {
                $logger = $event->getContext()->getContainer()->get(LoggerInterface::class);

                if ($logger instanceof LoggerInterface) {
                    $logger->error(
                        'ReceiveContext: The reception of an unmappable entity failed. Exception: '.$data->getThrowable()->getMessage()
                    );
                }
            }
        }

    }
}
