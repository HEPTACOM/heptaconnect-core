<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\ExploredPrimaryKeysToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @extends AbstractBufferedResultProcessingExplorer<string>
 */
final class EmissionJobDispatchingExplorer extends AbstractBufferedResultProcessingExplorer
{
    private ExploredPrimaryKeysToJobsConverterInterface $exploredPksToJobsConverter;

    private JobDispatcherContract $jobDispatcher;

    private LoggerInterface $logger;

    public function __construct(
        EntityType $entityType,
        ExploredPrimaryKeysToJobsConverterInterface $exploredPksToJobsConverter,
        JobDispatcherContract $jobDispatcher,
        LoggerInterface $logger,
        int $batchSize
    ) {
        parent::__construct($entityType, $batchSize);

        $this->exploredPksToJobsConverter = $exploredPksToJobsConverter;
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
    }

    protected function createBuffer(): CollectionInterface
    {
        return new StringCollection();
    }

    protected function processBuffer(CollectionInterface $buffer, ExploreContextInterface $context): void
    {
        $this->logger->debug('EmissionJobDispatchingExplorer: Flush a batch of publications', [
            'portalNodeKey' => $context->getPortalNodeKey(),
            'entityType' => $this->getSupportedEntityType(),
            'primaryKeys' => \implode(', ', \iterable_to_array($buffer)),
        ]);

        $jobs = $this->exploredPksToJobsConverter->convert(
            $context->getPortalNodeKey(),
            $this->getSupportedEntityType(),
            new StringCollection($buffer)
        );
        $this->jobDispatcher->dispatch($jobs);
    }

    protected function pushBuffer($value, CollectionInterface $buffer, ExploreContextInterface $context): void
    {
        if (\is_int($value) || \is_string($value)) {
            $this->logger->debug('EmissionJobDispatchingExplorer: Entity was explored and job dispatch is prepared', [
                'portalNodeKey' => $context->getPortalNodeKey(),
                'entityType' => $this->getSupportedEntityType(),
                'primaryKey' => (string) $value,
            ]);

            $buffer->push([(string) $value]);
        }
    }
}
