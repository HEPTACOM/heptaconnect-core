<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingServiceInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStatusReport\PortalNodeStatusReportPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeStatusReport\PortalNodeStatusReportResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeStatusReportUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeStatusReportUi implements PortalNodeStatusReportUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private StatusReportingServiceInterface $statusReportingService
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeStatusReportUiActionInterface::class);
    }

    public function report(PortalNodeStatusReportPayload $payloads, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payloads, $context]);
        $portalNodeKey = $payloads->getPortalNodeKey();

        foreach (\array_unique($payloads->getTopics()) as $topic) {
            $result = $this->statusReportingService->report($portalNodeKey, $topic)[$topic] ?? [];

            if ($result === []) {
                continue;
            }

            $success = (bool) ($result[$topic] ?? false);

            unset($result[$topic]);

            yield $topic => $trail->yield(new PortalNodeStatusReportResult($portalNodeKey, $topic, $success, $result));
        }

        $trail->end();
    }
}
