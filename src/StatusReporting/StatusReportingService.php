<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class StatusReportingService implements StatusReportingServiceInterface
{
    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $statusReporterStackCache = [];

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function report(PortalNodeKeyInterface $portalNodeKey, ?string $topic): array
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);
        /** @var StatusReporterCollection $statusReporters */
        $statusReporters = $container->get(StatusReporterCollection::class);
        /** @var StatusReportingContextInterface $context */
        $context = $container->get(PortalNodeContextInterface::class);
        $result = [];
        $topics = [];

        if (\is_null($topic)) {
            /** @var StatusReporterContract $statusReporter */
            foreach ($statusReporters as $statusReporter) {
                $topics[] = $statusReporter->supportsTopic();
            }

            $topics = \array_unique($topics);
        } else {
            $topics[] = $topic;
        }

        foreach ($topics as $topicName) {
            $result[$topicName] = $this->reportSingleTopic($portalNodeKey, $context, $statusReporters, $topicName);
        }

        return $result;
    }

    private function reportSingleTopic(
        PortalNodeKeyInterface $portalNodeKey,
        StatusReportingContextInterface $context,
        StatusReporterCollection $statusReporters,
        string $topic
    ): array {
        try {
            $stacks = $this->getStatusReporterStacks($portalNodeKey, $statusReporters, $topic);
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
    private function getStatusReporterStacks(
        PortalNodeKeyInterface $portalNodeKey,
        StatusReporterCollection $statusReporters,
        string $topic
    ): array {
        $cacheKey = \md5(\join([$this->storageKeyGenerator->serialize($portalNodeKey), $topic]));

        if (!isset($this->statusReporterStackCache[$cacheKey])) {
            foreach ($statusReporters as $statusReporter) {
                $stack = new StatusReporterStack([$statusReporter]);
                $this->statusReporterStackCache[$cacheKey][] = $stack;
            }
        }

        return \array_map(
            fn (StatusReporterStackInterface $receiverStack) => clone $receiverStack,
            $this->statusReporterStackCache[$cacheKey] ??= []
        );
    }
}
