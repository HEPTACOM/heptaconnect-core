<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackProcessorInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Psr\Log\LoggerInterface;

final class ExplorerStackProcessor implements ExplorerStackProcessorInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function processStack(ExplorerStackInterface $stack, ExploreContextInterface $context): iterable
    {
        try {
            foreach ($stack->next($context) as $key => $value) {
                if (\is_int($value)) {
                    yield $key => (string) $value;

                    continue;
                }

                yield $key => $value;
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EXPLORE_NO_THROW(), [
                'type' => $stack->supports(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }
    }
}
