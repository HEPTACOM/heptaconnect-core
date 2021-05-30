<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\RouteKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class EmissionActor implements EmissionActorInterface
{
    /**
     * @deprecated extract message bus from core
     */
    private JobDispatcherContract $jobDispatcher;

    private LoggerInterface $logger;

    private RouteRepositoryContract $routeRepository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        JobDispatcherContract $jobDispatcher,
        LoggerInterface $logger,
        RouteRepositoryContract $routeRepository,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
        $this->routeRepository = $routeRepository;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function performEmission(
        TypedMappingCollection $mappings,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void {
        if ($mappings->count() < 1) {
            return;
        }

        $routeKeys = new RouteKeyCollection($this->routeRepository->listBySourceAndEntityType(
            $context->getPortalNodeKey(),
            $mappings->getType()
        ));

        if ($routeKeys->count() < 1) {
            // TODO: add custom type for exception
            throw new \Exception(\sprintf(\implode(\PHP_EOL, [
                'Message is not routed. Add a route and re-explore this entity.',
                'source portal: %s',
                'data type: %s',
            ]), $this->storageKeyGenerator->serialize($context->getPortalNodeKey()), $mappings->getType()));
        }

        try {
            /** @var MappedDatasetEntityStruct $mappedDatasetEntityStruct */
            foreach ($stack->next($mappings, $context) as $mappedDatasetEntityStruct) {
                $jobs = new JobCollection();

                foreach ($routeKeys as $routeKey) {
                    $jobs->push([
                        new Reception(
                            new MappingComponentStruct(
                                $mappedDatasetEntityStruct->getMapping()->getPortalNodeKey(),
                                $mappedDatasetEntityStruct->getMapping()->getDatasetEntityClassName(),
                                $mappedDatasetEntityStruct->getMapping()->getExternalId()
                            ),
                            $routeKey,
                            $mappedDatasetEntityStruct->getDatasetEntity()
                        ),
                    ]);
                }

                $this->jobDispatcher->dispatch($jobs);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                'type' => $mappings->getType(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }
    }
}
