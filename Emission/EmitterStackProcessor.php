<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackProcessorInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Psr\Log\LoggerInterface;

final class EmitterStackProcessor implements EmitterStackProcessorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processStack(
        iterable $externalIds,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): TypedDatasetEntityCollection {
        $externalIds = \iterable_to_array($externalIds);
        $result = new TypedDatasetEntityCollection($stack->supports());

        if ($externalIds === []) {
            return $result;
        }

        try {
            foreach ($stack->next($externalIds, $context) as $entity) {
                if (!$this->validateResultEntity($entity, $stack)) {
                    continue;
                }

                $result->push([$entity]);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                'type' => $stack->supports(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }

        return $result;
    }

    private function validateResultEntity(DatasetEntityContract $entity, EmitterStackInterface $stack): bool
    {
        $externalId = $entity->getPrimaryKey();

        if ($externalId !== null) {
            return true;
        }

        $this->logger->critical(LogMessage::EMIT_NO_PRIMARY_KEY(), [
            'type' => $stack->supports(),
            'stack' => $stack,
            'entity' => $entity,
            'code' => 1637434358,
        ]);

        return false;
    }
}
