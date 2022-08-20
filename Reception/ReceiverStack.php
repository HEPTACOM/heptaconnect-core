<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Psr\Log\LoggerInterface;

final class ReceiverStack implements ReceiverStackInterface
{
    private ReceiverCollection $receivers;

    private LoggerInterface $logger;

    /**
     * @param iterable<array-key, ReceiverContract> $receivers
     */
    public function __construct(iterable $receivers, LoggerInterface $logger)
    {
        $this->receivers = new ReceiverCollection($receivers);
        $this->logger = $logger;
    }

    public function next(TypedDatasetEntityCollection $entities, ReceiveContextInterface $context): iterable
    {
        $receiver = $this->receivers->shift();

        if (!$receiver instanceof ReceiverContract) {
            return [];
        }

        $this->logger->debug('Execute FlowComponent receiver', [
            'receiver' => $receiver,
        ]);

        return $receiver->receive($entities, $context, $this);
    }
}
