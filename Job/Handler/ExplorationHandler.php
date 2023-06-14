<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\ExplorationHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Fail\JobFailPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Start\JobStartPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFailActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Psr\Log\LoggerInterface;

final class ExplorationHandler implements ExplorationHandlerInterface
{
    private ExploreServiceInterface $exploreService;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private JobStartActionInterface $jobStartAction;

    private JobFinishActionInterface $jobFinishAction;

    private JobFailActionInterface $jobFailAction;

    private LoggerInterface $logger;

    public function __construct(
        ExploreServiceInterface $exploreService,
        StorageKeyGeneratorContract $storageKeyGenerator,
        JobStartActionInterface $jobStartAction,
        JobFinishActionInterface $jobFinishAction,
        JobFailActionInterface $jobFailAction,
        LoggerInterface $logger
    ) {
        $this->exploreService = $exploreService;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->jobStartAction = $jobStartAction;
        $this->jobFinishAction = $jobFinishAction;
        $this->jobFailAction = $jobFailAction;
        $this->logger = $logger;
    }

    public function triggerExplorations(JobDataCollection $jobs): void
    {
        $keys = [];
        $types = [];
        $jobKeys = [];

        foreach ($jobs as $job) {
            $mapping = $job->getMappingComponent();
            $key = $this->storageKeyGenerator->serialize($mapping->getPortalNodeKey());

            $keys[$key] = $mapping->getPortalNodeKey();
            $types[$key][] = $mapping->getEntityType();
            $jobKeys[$key][] = $job->getJobKey();
        }

        foreach ($keys as $key => $portalNodeKey) {
            $type = $types[$key] ?? [];

            if ($type === []) {
                continue;
            }

            $jobKeys = new JobKeyCollection($jobKeys[$key]);

            $this->jobStartAction->start(new JobStartPayload(
                $jobKeys,
                new \DateTimeImmutable(),
                null
            ));

            try {
                $this->exploreService->explore(
                    $portalNodeKey,
                    $type
                );
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage(), [
                    'code' => 1686752879,
                    'jobKeys' => $jobKeys->asArray(),
                ]);

                $this->jobFailAction->fail(new JobFailPayload(
                    $jobKeys,
                    new \DateTimeImmutable(),
                    $exception->getMessage() . \PHP_EOL . 'Code: ' . $exception->getCode()
                ));

                continue;
            }

            $this->jobFinishAction->finish(new JobFinishPayload(
                $jobKeys,
                new \DateTimeImmutable(),
                null
            ));
        }
    }
}
