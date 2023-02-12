<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump;

use Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract\ServerRequestDumpCheckerInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Psr\Http\Message\ServerRequestInterface;

final class SampleRateServerRequestDumpChecker implements ServerRequestDumpCheckerInterface
{
    private WebHttpHandlerConfigurationFindActionInterface $configurationFindAction;

    public function __construct(
        WebHttpHandlerConfigurationFindActionInterface $configurationFindAction
    ) {
        $this->configurationFindAction = $configurationFindAction;
    }

    public function shallDump(HttpHandlerStackIdentifier $httpHandler, ServerRequestInterface $request): bool
    {
        $dumpSampleRate = $this->getDumpSampleRate($httpHandler);

        return $dumpSampleRate > 0 && \random_int(1, 100) <= $dumpSampleRate;
    }

    /**
     * @throws UnsupportedStorageKeyException
     *
     * @return int<0, 100>
     */
    private function getDumpSampleRate(HttpHandlerStackIdentifier $httpHandler): int
    {
        $result = $this->configurationFindAction->find(new WebHttpHandlerConfigurationFindCriteria(
            $httpHandler->getPortalNodeKey(),
            $httpHandler->getPath(),
            'dump-sample-rate'
        ));

        $sampleRate = (int) ($result->getValue()['value'] ?? 0);

        return \min(100, \max(0, $sampleRate));
    }
}
