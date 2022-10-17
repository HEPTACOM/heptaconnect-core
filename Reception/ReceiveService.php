<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionFlowReceiversFactoryInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Psr\Log\LoggerInterface;

final class ReceiveService implements ReceiveServiceInterface
{
    /**
     * @var array<array-key, ReceiverStackInterface|null>
     */
    private array $receiverStackCache = [];

    /**
     * @var array<array-key, ReceiveContextInterface>
     */
    private array $receiveContextCache = [];

    public function __construct(private ReceiveContextFactoryInterface $receiveContextFactory, private LoggerInterface $logger, private StorageKeyGeneratorContract $storageKeyGenerator, private ReceiverStackBuilderFactoryInterface $receiverStackBuilderFactory, private ReceiverStackProcessorInterface $receiverStackProcessor, private ReceptionFlowReceiversFactoryInterface $receptionFlowReceiversFactory)
    {
    }

    public function receive(TypedDatasetEntityCollection $entities, PortalNodeKeyInterface $portalNodeKey): void
    {
        if ($entities->isEmpty()) {
            return;
        }

        $type = $entities->getEntityType();
        $stack = $this->getReceiverStack($portalNodeKey, $type);

        if (!$stack instanceof ReceiverStackInterface) {
            $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), [
                'type' => $type,
                'portalNodeKey' => $portalNodeKey,
            ]);

            return;
        }

        $this->receiverStackProcessor->processStack($entities, $stack, $this->getReceiveContext($portalNodeKey));
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    private function getReceiverStack(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): ?ReceiverStackInterface {
        $cacheKey = \implode('', [$this->storageKeyGenerator->serialize($portalNodeKey), $entityType]);

        if (!\array_key_exists($cacheKey, $this->receiverStackCache)) {
            $builder = $this->receiverStackBuilderFactory
                ->createReceiverStackBuilder($portalNodeKey, $entityType)
                ->pushSource();

            if ($builder->isEmpty()) {
                $this->receiverStackCache[$cacheKey] = null;
            } else {
                $builder = $builder->pushDecorators();

                foreach ($this->receptionFlowReceiversFactory->createReceivers($portalNodeKey, $entityType) as $receiver) {
                    $builder = $builder->push($receiver);
                }

                $this->receiverStackCache[$cacheKey] = $builder->build();
            }
        }

        $result = $this->receiverStackCache[$cacheKey];

        if ($result instanceof ReceiverStackInterface) {
            return clone $result;
        }

        return null;
    }

    private function getReceiveContext(PortalNodeKeyInterface $portalNodeKey): ReceiveContextInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey);
        $this->receiveContextCache[$cacheKey] ??= $this->receiveContextFactory->createContext($portalNodeKey);

        return clone $this->receiveContextCache[$cacheKey];
    }
}
