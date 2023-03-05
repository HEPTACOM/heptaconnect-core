<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump;

use Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract\ServerRequestCycleDumpCheckerInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\ServerRequestCycle;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

final class SampleRateServerRequestCycleDumpChecker implements ServerRequestCycleDumpCheckerInterface
{
    public function __construct(
        private WebHttpHandlerConfigurationFindActionInterface $configurationFindAction
    ) {
    }

    public function shallDump(HttpHandlerStackIdentifier $httpHandler, ServerRequestCycle $requestCycle): bool
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
