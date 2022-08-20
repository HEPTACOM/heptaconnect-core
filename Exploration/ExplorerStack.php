<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Psr\Log\LoggerInterface;

final class ExplorerStack implements ExplorerStackInterface
{
    private ExplorerCollection $explorers;

    private LoggerInterface $logger;

    /**
     * @param iterable<array-key, ExplorerContract> $explorers
     */
    public function __construct(iterable $explorers, LoggerInterface $logger)
    {
        $this->explorers = new ExplorerCollection($explorers);
        $this->logger = $logger;
    }

    public function next(ExploreContextInterface $context): iterable
    {
        $explorer = $this->explorers->shift();

        if (!$explorer instanceof ExplorerContract) {
            return [];
        }

        $this->logger->debug('Execute FlowComponent explorer', [
            'explorer' => $explorer,
        ]);

        return $explorer->explore($context, $this);
    }
}
