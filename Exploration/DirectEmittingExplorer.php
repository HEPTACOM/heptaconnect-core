<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackProcessorInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class DirectEmittingExplorer extends ExplorerContract
{
    private EntityType $entityType;

    private DirectEmitter $directEmitter;

    private EmitterStackProcessorInterface $emitterStackProcessor;

    private EmitterStackInterface $emitterStack;

    private EmitContextInterface $emitContext;

    private LoggerInterface $logger;

    private int $batchSize;

    public function __construct(
        EntityType $entityType,
        DirectEmitter $directEmitter,
        EmitterStackProcessorInterface $emitterStackProcessor,
        EmitterStackInterface $emitterStack,
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        int $batchSize
    ) {
        $this->entityType = $entityType;
        $this->directEmitter = $directEmitter;
        $this->emitterStackProcessor = $emitterStackProcessor;
        $this->emitterStack = $emitterStack;
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->batchSize = $batchSize;
    }

    public function explore(ExploreContextInterface $context, ExplorerStackInterface $stack): iterable
    {
        $buffer = new TypedDatasetEntityCollection($this->getSupportedEntityType());

        try {
            foreach ($this->exploreNext($context, $stack) as $key => $value) {
                if ($value instanceof DatasetEntityContract) {
                    $primaryKey = $value->getPrimaryKey();

                    if ($primaryKey === null) {
                        $this->logger->error('DirectEmittingExplorer: Empty or invalid primary key was explored', [
                            'portalNodeKey' => $context->getPortalNodeKey(),
                            'entityType' => $this->getSupportedEntityType(),
                            'primaryKey' => $primaryKey,
                        ]);

                        continue;
                    }

                    $this->logger->debug('DirectEmittingExplorer: Entity was explored and job dispatch is prepared', [
                        'portalNodeKey' => $context->getPortalNodeKey(),
                        'entityType' => $this->getSupportedEntityType(),
                        'primaryKey' => $primaryKey,
                    ]);

                    $buffer->push([$value]);

                    if ($buffer->count() >= $this->batchSize) {
                        $this->dispatchBuffer($context->getPortalNodeKey(), $buffer);
                    }
                }

                yield $key => $value;
            }
        } finally {
            while ($buffer->count() > 0) {
                $this->dispatchBuffer($context->getPortalNodeKey(), $buffer);
            }
        }
    }

    protected function supports(): string
    {
        return (string) $this->entityType;
    }

    private function dispatchBuffer(PortalNodeKeyInterface $portalNodeKey, TypedDatasetEntityCollection $buffer): void
    {
        $batchSize = $this->batchSize;
        $entities = new TypedDatasetEntityCollection($buffer->getEntityType());

        for ($step = 0; $step < $batchSize && $buffer->count() > 0; ++$step) {
            /** @var DatasetEntityContract $item */
            $item = $buffer->shift();
            $entities->push([$item]);
        }

        $pks = \iterable_to_array($entities->column('getPrimaryKey'));

        $this->logger->debug('DirectEmittingExplorer: Flush a batch of direct emissions', [
            'portalNodeKey' => $portalNodeKey,
            'entityType' => $this->getSupportedEntityType(),
            'primaryKeys' => \implode(', ', $pks),
        ]);

        $this->directEmitter->getEntities()->clear();
        $this->directEmitter->getEntities()->push($entities);
        $this->emitterStackProcessor->processStack($pks, clone $this->emitterStack, $this->emitContext);
    }
}
