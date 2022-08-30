<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Reception\Support\LockAttachable;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class LockingReceiver extends ReceiverContract
{
    private EntityType $entityType;

    private LoggerInterface $logger;

    public function __construct(EntityType $entityType, LoggerInterface $logger)
    {
        $this->entityType = $entityType;
        $this->logger = $logger;
    }

    public function receive(
        TypedDatasetEntityCollection $entities,
        ReceiveContextInterface $context,
        ReceiverStackInterface $stack
    ): iterable {
        $entityType = $entities->getEntityType();
        $result = new TypedDatasetEntityCollection($entityType);
        $todo = new TypedDatasetEntityCollection($entityType, $entities);

        for ($tries = 0; $tries < 3 && $todo->count() > 0; ++$tries) {
            $lockablesAndLocked = new TypedDatasetEntityCollection($entityType);
            $unlockables = new TypedDatasetEntityCollection($entityType);
            $newToDo = new TypedDatasetEntityCollection($entityType);

            $this->lockEntities($todo, $lockablesAndLocked, $unlockables, $newToDo, $context->getPortalNodeKey());

            try {
                $entitySlice = new TypedDatasetEntityCollection($entityType);
                $entitySlice->push($lockablesAndLocked);
                $entitySlice->push($unlockables);
                $result->push($this->receiveNext(clone $stack, $entitySlice, $context));
            } finally {
                $this->unlockEntities($lockablesAndLocked, $context->getPortalNodeKey());
            }

            $todo = $newToDo;
        }

        if ($todo->count() > 0) {
            $this->logger->critical('Failed to lock and receive entities after retrying', [
                'portalNodeKey' => $context->getPortalNodeKey(),
                'entityType' => (string) $this->getSupportedEntityType(),
                'primaryKeys' => \iterable_to_array($todo->column('getPrimaryKey')),
                'code' => 1661818272,
                'retries' => 3,
            ]);
        }

        return $result;
    }

    protected function supports(): string
    {
        return (string) $this->entityType;
    }

    /**
     * @param iterable<DatasetEntityContract> $entities
     */
    private function lockEntities(
        iterable $entities,
        TypedDatasetEntityCollection $lockablesAndLocked,
        TypedDatasetEntityCollection $unlockables,
        TypedDatasetEntityCollection $newToDo,
        PortalNodeKeyInterface $portalNodeKey
    ): void {
        foreach ($entities as $entity) {
            $lockAttachment = $entity->getAttachment(LockAttachable::class);

            if (!$lockAttachment instanceof LockAttachable) {
                $unlockables->push([$entity]);

                continue;
            }

            if ($lockAttachment->getLock()->acquire()) {
                $lockablesAndLocked->push([$entity]);

                $this->logger->debug('Locking an entity', [
                    'portalNodeKey' => $portalNodeKey,
                    'entityType' => (string) $this->getSupportedEntityType(),
                    'primaryKey' => $entity->getPrimaryKey(),
                ]);

                continue;
            }

            $newToDo->push([$entity]);
        }
    }

    /**
     * @param iterable<DatasetEntityContract> $entities
     */
    private function unlockEntities(iterable $entities, PortalNodeKeyInterface $portalNodeKey): void
    {
        foreach ($entities as $entity) {
            $lockAttachment = $entity->getAttachment(LockAttachable::class);

            if (!$lockAttachment instanceof LockAttachable) {
                $this->logger->error('Unlocking of an entity failed because the lock is missing', [
                    'portalNodeKey' => $portalNodeKey,
                    'entityType' => (string) $this->getSupportedEntityType(),
                    'primaryKey' => $entity->getPrimaryKey(),
                    'code' => 1661818270,
                ]);

                continue;
            }

            try {
                $lockAttachment->getLock()->release();
            } catch (\Throwable $throwable) {
                $this->logger->error('Unlocking of an entity failed because the release of the lock failed', [
                    'portalNodeKey' => $portalNodeKey,
                    'entityType' => (string) $this->getSupportedEntityType(),
                    'primaryKey' => $entity->getPrimaryKey(),
                    'code' => 1661818271,
                    'exception' => $throwable,
                ]);
            }
        }
    }
}
