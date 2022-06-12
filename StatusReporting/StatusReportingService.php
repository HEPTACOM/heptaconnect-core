<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

final class StatusReportingService implements StatusReportingServiceInterface
{
    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    /**
     * @return array<array-key, StatusReporterStackInterface>
     */
    private array $statusReporterStackCache = [];

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private StatusReportingContextFactoryInterface $statusReportingContextFactory;

    public function __construct(
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        StatusReportingContextFactoryInterface $statusReportingContextFactory
    ) {
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->statusReportingContextFactory = $statusReportingContextFactory;
    }

    public function report(PortalNodeKeyInterface $portalNodeKey, ?string $topic): array
    {
        $flowComponentRegistry = $this->portalStackServiceContainerFactory
            ->create($portalNodeKey)
            ->getFlowComponentRegistry();
        $statusReporters = new StatusReporterCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $statusReporters->push($flowComponentRegistry->getStatusReporters($source));
        }

        $context = $this->statusReportingContextFactory->factory($portalNodeKey);
        $result = [];
        $topics = [];

        if ($topic === null) {
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
        $topicStatusReporters = new StatusReporterCollection($statusReporters->bySupportedTopic($topic));

        if ($topicStatusReporters->count() < 1) {
            $this->logger->critical(LogMessage::STATUS_REPORT_NO_STATUS_REPORTER_FOR_TYPE(), [
                'topic' => $topic,
                'portalNodeKey' => $portalNodeKey,
            ]);

            return [];
        }

        $stack = $this->getStatusReporterStack($portalNodeKey, $topicStatusReporters, $topic);

        try {
            return $stack->next($context);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::STATUS_REPORT_NO_THROW(), [
                'topic' => $topic,
                'portalNodeKey' => $portalNodeKey,
                'stack' => $stack,
                'exception' => $exception,
                'context' => $context,
            ]);

            return [
                $topic => false,
                'exception' => $exception->getMessage(),
            ];
        }
    }

    private function getStatusReporterStack(
        PortalNodeKeyInterface $portalNodeKey,
        StatusReporterCollection $statusReporters,
        string $topic
    ): StatusReporterStackInterface {
        $cacheKey = \md5(\implode('', [$this->storageKeyGenerator->serialize($portalNodeKey), $topic]));

        if (!isset($this->statusReporterStackCache[$cacheKey])) {
            $this->statusReporterStackCache[$cacheKey] = new StatusReporterStack($statusReporters, $this->logger);
        }

        return clone $this->statusReporterStackCache[$cacheKey];
    }
}
