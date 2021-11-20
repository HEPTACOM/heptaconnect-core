<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class EmissionActor implements EmissionActorInterface
{
    /**
     * @deprecated extract message bus from core
     */
    private JobDispatcherContract $jobDispatcher;

    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ReceptionRouteListActionInterface $receptionRouteListAction;

    public function __construct(
        JobDispatcherContract $jobDispatcher,
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ReceptionRouteListActionInterface $receptionRouteListAction
    ) {
        /* @phpstan-ignore-next-line */
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->receptionRouteListAction = $receptionRouteListAction;
    }

    public function performEmission(
        iterable $externalIds,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void {
        $externalIds = \iterable_to_array($externalIds);

        if ($externalIds === []) {
            return;
        }

        /** @var ReceptionRouteListResult[] $receptionRoutes */
        $receptionRoutes = \iterable_to_array($this->receptionRouteListAction->list(new ReceptionRouteListCriteria($context->getPortalNodeKey(), $stack->supports())));

        if ($receptionRoutes === []) {
            // TODO: add custom type for exception
            throw new \Exception(\sprintf(\implode(\PHP_EOL, ['Message is not routed. Add a route and re-explore this entity.', 'source portal: %s', 'data type: %s']), $this->storageKeyGenerator->serialize($context->getPortalNodeKey()), $stack->supports()));
        }

        try {
            $jobs = new JobCollection();

            /** @var DatasetEntityContract $entity */
            foreach ($stack->next($externalIds, $context) as $entity) {
                foreach ($receptionRoutes as $receptionRoute) {
                    $externalId = $entity->getPrimaryKey();

                    if (\is_null($externalId)) {
                        $this->logger->critical(LogMessage::EMIT_NO_PRIMARY_KEY(), [
                            'type' => $stack->supports(),
                            'stack' => $stack,
                            'entity' => $entity,
                            'code' => 1637434358,
                        ]);

                        continue;
                    }

                    $jobs->push([
                        new Reception(
                            new MappingComponentStruct($context->getPortalNodeKey(), $stack->supports(), $externalId),
                            $receptionRoute->getRoute(),
                            $entity
                        ),
                    ]);
                }
            }

            /* @phpstan-ignore-next-line */
            $this->jobDispatcher->dispatch($jobs);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                'type' => $stack->supports(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }
    }
}
