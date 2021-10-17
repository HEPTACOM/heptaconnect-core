<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\PostProcessorContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Log\LoggerInterface;

class MarkAsFailedPostProcessor extends PostProcessorContract
{
    private MappingServiceInterface $mappingService;

    private LoggerInterface $logger;

    public function __construct(MappingServiceInterface $mappingService, LoggerInterface $logger)
    {
        $this->mappingService = $mappingService;
        $this->logger = $logger;
    }

    public function handle(PostReceptionEvent $event): void
    {
        $markAsFailedData = \iterable_map(
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
                $logger = $event->getContext()->getContainer()->get(LoggerInterface::class) ?? $this->logger;

                $logger->error(LogMessage::MARK_AS_FAILED_ENTITY_IS_UNMAPPED(), [
                    'throwable' => $data->getThrowable(),
                    'data' => $data,
                ]);
            }
        }
    }
}
