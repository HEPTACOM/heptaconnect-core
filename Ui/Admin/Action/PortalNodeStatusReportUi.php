<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingServiceInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStatusReport\PortalNodeStatusReportPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStatusReport\PortalNodeStatusReportResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeStatusReportUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeStatusReportUi implements PortalNodeStatusReportUiActionInterface
{
    private StatusReportingServiceInterface $statusReportingService;

    public function __construct(StatusReportingServiceInterface $statusReportingService)
    {
        $this->statusReportingService = $statusReportingService;
    }

    public function report(PortalNodeStatusReportPayload $payloads, UiActionContextInterface $context): iterable
    {
        $portalNodeKey = $payloads->getPortalNodeKey();

        foreach (\array_unique($payloads->getTopics()) as $topic) {
            $result = $this->statusReportingService->report($portalNodeKey, $topic)[$topic] ?? [];

            if ($result === []) {
                continue;
            }

            $success = (bool) ($result[$topic] ?? false);

            unset($result[$topic]);

            yield $topic => new PortalNodeStatusReportResult($portalNodeKey, $topic, $success, $result);
        }
    }
}
