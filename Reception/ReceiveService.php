<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Psr\Log\LoggerInterface;

final class ReceiveService implements ReceiveServiceInterface
{
    private ReceiveContextFactoryInterface $receiveContextFactory;

    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    /**
     * @var array<array-key, ReceiverStackInterface|null>
     */
    private array $receiverStackCache = [];

    /**
     * @var array<array-key, ReceiveContextInterface>
     */
    private array $receiveContextCache = [];

    private ReceiverStackBuilderFactoryInterface $receiverStackBuilderFactory;

    private ReceptionActorInterface $receptionActor;

    public function __construct(
        ReceiveContextFactoryInterface $receiveContextFactory,
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ReceiverStackBuilderFactoryInterface $receiverStackBuilderFactory,
        ReceptionActorInterface $receptionActor
    ) {
        $this->receiveContextFactory = $receiveContextFactory;
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->receiverStackBuilderFactory = $receiverStackBuilderFactory;
        $this->receptionActor = $receptionActor;
    }

    public function receive(TypedDatasetEntityCollection $entities, PortalNodeKeyInterface $portalNodeKey): void
    {
        if ($entities->count() === 0) {
            return;
        }

        $type = $entities->getType();
        $stack = $this->getReceiverStack($portalNodeKey, $type);

        if (!$stack instanceof ReceiverStackInterface) {
            $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), [
                'type' => $type,
                'portalNodeKey' => $portalNodeKey,
            ]);

            return;
        }

        $this->receptionActor->performReception($entities, $stack, $this->getReceiveContext($portalNodeKey));
    }

    /**
     * @param class-string<DatasetEntityContract> $entityType
     *
     * @throws UnsupportedStorageKeyException
     */
    private function getReceiverStack(PortalNodeKeyInterface $portalNodeKey, string $entityType): ?ReceiverStackInterface
    {
        $cacheKey = \implode('', [$this->storageKeyGenerator->serialize($portalNodeKey), $entityType]);

        if (!\array_key_exists($cacheKey, $this->receiverStackCache)) {
            $builder = $this->receiverStackBuilderFactory
                ->createReceiverStackBuilder($portalNodeKey, $entityType)
                ->pushSource()
                // TODO break when source is already empty
                ->pushDecorators();

            $this->receiverStackCache[$cacheKey] = $builder->isEmpty() ? null : $builder->build();
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
