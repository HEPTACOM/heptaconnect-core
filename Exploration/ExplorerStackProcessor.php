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
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processStack(ExplorerStackInterface $stack, ExploreContextInterface $context): iterable
    {
        try {
            yield from $stack->next($context);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EXPLORE_NO_THROW(), [
                'type' => $stack->supports(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }
    }
}
