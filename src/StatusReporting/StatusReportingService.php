<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class StatusReportingService implements StatusReportingServiceInterface
{
    private StatusReportingContextFactoryInterface $statusReportingContextFactory;

    private LoggerInterface $logger;

    private PortalRegistryInterface $portalRegistry;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $statusReporterStackCache = [];

    public function __construct(
        StatusReportingContextFactoryInterface $statusReportingContextFactory,
        LoggerInterface $logger,
        PortalRegistryInterface $portalRegistry,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->statusReportingContextFactory = $statusReportingContextFactory;
        $this->logger = $logger;
        $this->portalRegistry = $portalRegistry;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function report(PortalNodeKeyInterface $portalNodeKey, ?string $topic): array
    {
        $result = [];

        $topics = [];

        if (\is_null($topic)) {
            $portal = $this->portalRegistry->getPortal($portalNodeKey);

            /** @var StatusReporterContract $statusReporter */
            foreach ($portal->getStatusReporters()->getIterator() as $statusReporter) {
                $topics[] = $statusReporter->supportsTopic();
            }

            $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);

            foreach ($portalExtensions->getStatusReporters()->getIterator() as $statusReporter) {
                $topics[] = $statusReporter->supportsTopic();
            }

            $topics = \array_unique($topics);
        } else {
            $topics[] = $topic;
        }

        foreach ($topics as $topicName) {
            $result[$topicName] = $this->reportSingleTopic($portalNodeKey, $topicName);
        }

        return $result;
    }

    private function reportSingleTopic(PortalNodeKeyInterface $portalNodeKey, string $topic): array
    {
        try {
            $stacks = $this->getStatusReporterStacks($portalNodeKey, $topic);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::STATUS_REPORT_NO_STACKS(), [
                'topic' => $topic,
                'portalNodeKey' => $portalNodeKey,
                'exception' => $exception,
            ]);

            return [];
        }

        if (empty($stacks)) {
            $this->logger->critical(LogMessage::STATUS_REPORT_NO_RECEIVER_FOR_TYPE(), [
                'topic' => $topic,
                'portalNodeKey' => $portalNodeKey,
            ]);

            return [];
        }

        $results = [];
        $context = $this->statusReportingContextFactory->factory($portalNodeKey);

        /** @var StatusReporterStackInterface $stack */
        foreach ($stacks as $stack) {
            try {
                $results[] = $stack->next($context);
            } catch (\Throwable $exception) {
                $this->logger->critical(LogMessage::STATUS_REPORT_NO_THROW(), [
                    'topic' => $topic,
                    'portalNodeKey' => $portalNodeKey,
                    'stack' => $stack,
                    'exception' => $exception,
                    'results' => $results,
                    'context' => $context,
                ]);
            }
        }

        if (empty($results)) {
            return [];
        }

        return \array_merge_recursive(...$results);
    }

    /**
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterStackInterface>
     */
    private function getStatusReporterStacks(PortalNodeKeyInterface $portalNodeKey, string $topic): array
    {
        $cacheKey = \md5(\join([$this->storageKeyGenerator->serialize($portalNodeKey), $topic]));

        if (!isset($this->statusReporterStackCache[$cacheKey])) {
            $portal = $this->portalRegistry->getPortal($portalNodeKey);
            $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);
            $statusReporters = \iterable_to_array($portal->getStatusReporters()->bySupportedTopic($topic));
            $statusReporterDecorators = \iterable_to_array($portalExtensions->getStatusReporters()->bySupportedTopic($topic));

            if ($statusReporters) {
                foreach ($statusReporters as $statusReporter) {
                    $stack = new StatusReporterStack([...$statusReporterDecorators, $statusReporter]);
                    $this->statusReporterStackCache[$cacheKey][] = $stack;
                }
            } elseif ($statusReporterDecorators) {
                $stack = new StatusReporterStack([...$statusReporterDecorators]);
                $this->statusReporterStackCache[$cacheKey][] = $stack;
            }
        }

        return \array_map(
            fn (StatusReporterStackInterface $receiverStack) => clone $receiverStack,
            $this->statusReporterStackCache[$cacheKey] ??= []
        );
    }
}
