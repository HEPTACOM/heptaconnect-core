<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\ExploredPrimaryKeysToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class EmissionJobDispatchingExplorer extends ExplorerContract
{
    private EntityType $entityType;

    private ExploredPrimaryKeysToJobsConverterInterface $exploredPksToJobsConverter;

    private JobDispatcherContract $jobDispatcher;

    private LoggerInterface $logger;

    private int $batchSize;

    public function __construct(
        EntityType $entityType,
        ExploredPrimaryKeysToJobsConverterInterface $exploredPksToJobsConverter,
        JobDispatcherContract $jobDispatcher,
        LoggerInterface $logger,
        int $batchSize
    ) {
        $this->entityType = $entityType;
        $this->exploredPksToJobsConverter = $exploredPksToJobsConverter;
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
        $this->batchSize = $batchSize;
    }

    public function explore(ExploreContextInterface $context, ExplorerStackInterface $stack): iterable
    {
        $buffer = new StringCollection();

        try {
            foreach ($this->exploreNext($context, $stack) as $key => $value) {
                if (\is_int($value) || \is_string($value)) {
                    $this->logger->debug('EmissionJobDispatchingExplorer: Entity was explored and job dispatch is prepared', [
                        'portalNodeKey' => $context->getPortalNodeKey(),
                        'entityType' => $this->getSupportedEntityType(),
                        'primaryKey' => (string) $value,
                    ]);

                    $buffer->push([(string) $value]);

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

    private function dispatchBuffer(PortalNodeKeyInterface $portalNodeKey, StringCollection $buffer): void
    {
        $batchSize = $this->batchSize;
        $pks = new StringCollection();

        for ($step = 0; $step < $batchSize && $buffer->count() > 0; ++$step) {
            /** @var string $item */
            $item = $buffer->shift();
            $pks->push([$item]);
        }

        $this->logger->debug('EmissionJobDispatchingExplorer: Flush a batch of publications', [
            'portalNodeKey' => $portalNodeKey,
            'entityType' => $this->getSupportedEntityType(),
            'primaryKeys' => \implode(', ', \iterable_to_array($pks)),
        ]);

        $jobs = $this->exploredPksToJobsConverter->convert($portalNodeKey, $this->getSupportedEntityType(), $pks);
        $this->jobDispatcher->dispatch($jobs);
    }
}
