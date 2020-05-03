<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Receive\Contract\ReceiverRegistryInterface;
use Heptacom\HeptaConnect\Core\Receive\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use Psr\Log\LoggerInterface;

class ReceiveService implements ReceiveServiceInterface
{
    private ReceiverRegistryInterface $receiverRegistry;

    private MappingServiceInterface $mappingService;

    private ReceiveContextInterface $receiveContext;

    private LoggerInterface $logger;

    public function __construct(
        ReceiverRegistryInterface $receiverRegistry,
        MappingServiceInterface $mappingService,
        ReceiveContextInterface $receiveContext,
        LoggerInterface $logger
    ) {
        $this->receiverRegistry = $receiverRegistry;
        $this->mappingService = $mappingService;
        $this->receiveContext = $receiveContext;
        $this->logger = $logger;
    }

    public function receive(MappedDatasetEntityCollection $mappedDatasetEntities): void
    {
        /** @var array<
         *     class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>,
         *     MappedDatasetEntityCollection
         * > $mappingsByType
         */
        $mappingsByType = [];

        /** @var MappedDatasetEntityStruct $mappedDatasetEntity */
        foreach ($mappedDatasetEntities as $mappedDatasetEntity) {
            $mappingType = $this->mappingService->getDatasetEntityClassName($mappedDatasetEntity->getMapping());
            $mappingsByType[$mappingType] ??= new MappedDatasetEntityCollection();
            $mappingsByType[$mappingType]->push($mappedDatasetEntity);
        }

        foreach ($mappingsByType as $type => $typedMappings) {
            $receivers = $this->receiverRegistry->bySupport($type);

            if (empty($receivers)) {
                $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), ['type' => $type]);
                continue;
            }

            /** @var ReceiverInterface $receiver */
            foreach ($receivers as $receiver) {
                try {
                    // TODO chunk
                    foreach ($receiver->receive($typedMappings, $this->receiveContext) as $externalId => $mapping) {
                        $this->mappingService->setExternalId($mapping, $externalId);
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::RECEIVE_NO_THROW(), [
                        'type' => $type,
                        'receiver' => \get_class($receiver),
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }
}
