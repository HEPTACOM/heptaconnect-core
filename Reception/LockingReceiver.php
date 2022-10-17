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
use Symfony\Component\Lock\LockInterface;

final class LockingReceiver extends ReceiverContract
{
    public function __construct(private EntityType $entityType, private LoggerInterface $logger)
    {
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
            // allow repetitive tries to recover from whatever blocked the locking
            \usleep(20 * $tries);

            $slice = new TypedDatasetEntityCollection($entityType);
            $newToDo = new TypedDatasetEntityCollection($entityType);

            $locks = $this->lockEntities($todo, $slice, $newToDo, $context->getPortalNodeKey());

            try {
                $result->push($this->receiveNext(clone $stack, $slice, $context));
            } finally {
                $this->unlockLocks($locks, $context->getPortalNodeKey());
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
     *
     * @return LockInterface[]
     */
    private function lockEntities(
        iterable $entities,
        TypedDatasetEntityCollection $slice,
        TypedDatasetEntityCollection $newToDo,
        PortalNodeKeyInterface $portalNodeKey
    ): array {
        $lockedLocks = [];

        foreach ($entities as $entityKey => $entity) {
            $lockAttachment = $entity->getAttachment(LockAttachable::class);

            if (!$lockAttachment instanceof LockAttachable) {
                $slice->push([$entity]);

                continue;
            }

            $lock = $lockAttachment->getLock();

            if ($lock->acquire()) {
                $slice->push([$entity]);
                $lockedLocks[$entity->getPrimaryKey() ?? $entityKey] = $lock;

                $this->logger->debug('Locking an entity', [
                    'portalNodeKey' => $portalNodeKey,
                    'entityType' => (string) $this->getSupportedEntityType(),
                    'primaryKey' => $entity->getPrimaryKey(),
                    'entityLoopKey' => $entityKey,
                ]);

                continue;
            }

            $newToDo->push([$entity]);
        }

        return $lockedLocks;
    }

    /**
     * @param array<array-key, LockInterface> $locks
     */
    private function unlockLocks(array $locks, PortalNodeKeyInterface $portalNodeKey): void
    {
        foreach ($locks as $lockKey => $lock) {
            try {
                $lock->release();
            } catch (\Throwable $throwable) {
                $this->logger->error('Unlocking of an entity failed because the release of the lock failed', [
                    'portalNodeKey' => $portalNodeKey,
                    'entityType' => (string) $this->getSupportedEntityType(),
                    'lockKey' => $lockKey,
                    'code' => 1661818271,
                    'exception' => $throwable,
                ]);
            }
        }
    }
}
