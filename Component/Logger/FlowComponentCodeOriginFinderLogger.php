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
    private LoggerInterface $decorated;

    private EmitterCodeOriginFinderInterface $emitterCodeOriginFinder;

    private ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder;

    private ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder;

    private StatusReporterCodeOriginFinderInterface $statusReporterCodeOriginFinder;

    private HttpHandlerCodeOriginFinderInterface $httpHandlerCodeOriginFinder;

    public function __construct(
        LoggerInterface $decorated,
        EmitterCodeOriginFinderInterface $emitterCodeOriginFinder,
        ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder,
        ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder,
        StatusReporterCodeOriginFinderInterface $statusReporterCodeOriginFinder,
        HttpHandlerCodeOriginFinderInterface $httpHandlerCodeOriginFinder
    ) {
        $this->decorated = $decorated;
        $this->emitterCodeOriginFinder = $emitterCodeOriginFinder;
        $this->explorerCodeOriginFinder = $explorerCodeOriginFinder;
        $this->receiverCodeOriginFinder = $receiverCodeOriginFinder;
        $this->statusReporterCodeOriginFinder = $statusReporterCodeOriginFinder;
        $this->httpHandlerCodeOriginFinder = $httpHandlerCodeOriginFinder;
    }

    public function log($level, $message, array $context = array())
    {
        foreach ($context as $key => &$value) {
            try {
                if ($value instanceof EmitterContract) {
                    $value = (string) $this->emitterCodeOriginFinder->findOrigin($value);
                } else if ($value instanceof ExplorerContract) {
                    $value = (string) $this->explorerCodeOriginFinder->findOrigin($value);
                } else if ($value instanceof ReceiverContract) {
                    $value = (string) $this->receiverCodeOriginFinder->findOrigin($value);
                } else if ($value instanceof StatusReporterContract) {
                    $value = (string) $this->statusReporterCodeOriginFinder->findOrigin($value);
                } else if ($value instanceof HttpHandlerContract) {
                    $value = (string) $this->httpHandlerCodeOriginFinder->findOrigin($value);
                }
            } catch (\Throwable $throwable) {
                $this->decorated->log(LogLevel::DEBUG, 'Could not evaluate code origin in for log message', [
                    'code' => $throwable->getCode(),
                    'flow_component_class' => \get_class($value),
                    'context_key' => $key,
                ]);
            }
        }

        $this->decorated->log($level, $message, $context);
    }
}
