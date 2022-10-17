<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Logger;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FlowComponentCodeOriginFinderLogger extends AbstractLogger
{
    public function __construct(
        private LoggerInterface $decorated,
        private EmitterCodeOriginFinderInterface $emitterCodeOriginFinder,
        private ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder,
        private ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder,
        private StatusReporterCodeOriginFinderInterface $statusReporterCodeOriginFinder,
        private HttpHandlerCodeOriginFinderInterface $httpHandlerCodeOriginFinder
    ) {
    }

    public function log($level, $message, array $context = []): void
    {
        foreach ($context as $key => &$value) {
            try {
                if ($value instanceof EmitterContract) {
                    $value = (string) $this->emitterCodeOriginFinder->findOrigin($value);
                } elseif ($value instanceof ExplorerContract) {
                    $value = (string) $this->explorerCodeOriginFinder->findOrigin($value);
                } elseif ($value instanceof ReceiverContract) {
                    $value = (string) $this->receiverCodeOriginFinder->findOrigin($value);
                } elseif ($value instanceof StatusReporterContract) {
                    $value = (string) $this->statusReporterCodeOriginFinder->findOrigin($value);
                } elseif ($value instanceof HttpHandlerContract) {
                    $value = (string) $this->httpHandlerCodeOriginFinder->findOrigin($value);
                }
            } catch (\Throwable $throwable) {
                $this->decorated->log(LogLevel::DEBUG, 'Could not evaluate code origin in for log message', [
                    'code' => $throwable->getCode(),
                    'flow_component_class' => $value::class,
                    'context_key' => $key,
                ]);
            }
        }

        $this->decorated->log($level, $message, $context);
    }
}
