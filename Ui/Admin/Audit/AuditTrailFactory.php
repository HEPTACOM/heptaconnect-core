<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditableDataSerializerInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;
use Heptacom\HeptaConnect\Dataset\Base\TaggedCollection\TaggedStringCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailBegin\UiAuditTrailBeginPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailEnd\UiAuditTrailEndPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogError\UiAuditTrailLogErrorPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogError\UiAuditTrailLogErrorPayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogOutput\UiAuditTrailLogOutputPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailBeginActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailEndActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailLogErrorActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailLogOutputActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\UiAuditTrailKeyInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Audit\UiAuditContext;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Audit\AuditableDataAwareInterface;
use Psr\Log\LoggerInterface;

final class AuditTrailFactory implements AuditTrailFactoryInterface
{
    private DeepObjectIteratorContract $deepObjectIterator;

    private AuditableDataSerializerInterface $auditableDataSerializer;

    private UiAuditTrailBeginActionInterface $uiAuditTrailBeginAction;

    private UiAuditTrailLogOutputActionInterface $uiAuditTrailLogOutputAction;

    private UiAuditTrailLogErrorActionInterface $uiAuditTrailLogErrorAction;

    private UiAuditTrailEndActionInterface $uiAuditTrailEndAction;

    private LoggerInterface $logger;

    public function __construct(
        DeepObjectIteratorContract $deepObjectIterator,
        AuditableDataSerializerInterface $auditableDataSerializer,
        UiAuditTrailBeginActionInterface $uiAuditTrailBeginAction,
        UiAuditTrailLogOutputActionInterface $uiAuditTrailLogOutputAction,
        UiAuditTrailLogErrorActionInterface $uiAuditTrailLogErrorAction,
        UiAuditTrailEndActionInterface $uiAuditTrailEndAction,
        LoggerInterface $logger
    ) {
        $this->deepObjectIterator = $deepObjectIterator;
        $this->auditableDataSerializer = $auditableDataSerializer;
        $this->uiAuditTrailBeginAction = $uiAuditTrailBeginAction;
        $this->uiAuditTrailLogOutputAction = $uiAuditTrailLogOutputAction;
        $this->uiAuditTrailLogErrorAction = $uiAuditTrailLogErrorAction;
        $this->uiAuditTrailEndAction = $uiAuditTrailEndAction;
        $this->logger = $logger;
    }

    public function create(UiActionInterface $uiAction, UiAuditContext $auditContext, array $inbound): AuditTrailInterface
    {
        try {
            $key = $this->uiAuditTrailBeginAction->begin(new UiAuditTrailBeginPayload(
                'admin',
                $auditContext->getUiIdentifier(),
                $uiAction::class(),
                $auditContext->getUserIdentifier(),
                $this->unpackAuditables($inbound)
            ))->getUiAuditTrailKey();
        } catch (\Throwable $throwable) {
            $this->logger->critical('Starting a UI audit failed', [
                'code' => 1663677420,
                'throwable' => $throwable,
                'uiType' => 'admin',
                'uiIdentifier' => $auditContext->getUiIdentifier(),
                'uiActionType' => $uiAction::class(),
                'userIdentifier' => $auditContext->getUserIdentifier(),
            ]);

            return new NullAuditTrail();
        }

        return new AuditTrail(
            fn (object $output) => $this->logOutput($key, $output),
            fn (\Throwable $throwable) => $this->logThrowable($key, $throwable),
            fn () => $this->logEnd($key),
        );
    }

    private function logOutput(UiAuditTrailKeyInterface $auditTrailKey, object $output): void
    {
        try {
            $this->uiAuditTrailLogOutputAction->logOutput(new UiAuditTrailLogOutputPayload(
                $auditTrailKey,
                $this->unpackAuditables(['output' => $output])
            ));
        } catch (\Throwable $throwable) {
            $this->logger->error('Logging UI audit output failed', [
                'code' => 1663677421,
                'throwable' => $throwable,
                'auditTrailKey' => $auditTrailKey,
            ]);
        }
    }

    private function logThrowable(UiAuditTrailKeyInterface $auditTrailKey, \Throwable $auditException): void
    {
        $payloads = new UiAuditTrailLogErrorPayloadCollection();

        foreach ($this->unrollThrowable($auditException) as $depth => $exception) {
            $payloads->push([new UiAuditTrailLogErrorPayload(
                $auditTrailKey,
                \get_class($exception),
                $depth,
                $exception->getMessage(),
                (string) $exception->getCode()
            )]);
        }

        if (!$payloads->isEmpty()) {
            try {
                $this->uiAuditTrailLogErrorAction->logError($payloads);
            } catch (\Throwable $throwable) {
                $this->logger->error('Logging UI audit exception failed', [
                    'code' => 1663677422,
                    'auditException' => $auditException,
                    'throwable' => $throwable,
                    'auditTrailKey' => $auditTrailKey,
                ]);
            }
        }
    }

    private function logEnd(UiAuditTrailKeyInterface $auditTrailKey): void
    {
        try {
            $this->uiAuditTrailEndAction->end(new UiAuditTrailEndPayload($auditTrailKey));
        } catch (\Throwable $throwable) {
            $this->logger->error('Marking end of UI audit failed', [
                'code' => 1663677423,
                'throwable' => $throwable,
                'auditTrailKey' => $auditTrailKey,
            ]);
        }
    }

    /**
     * @return iterable<int, \Throwable>
     */
    private function unrollThrowable(\Throwable $throwable): iterable
    {
        $alreadyYielded = [];

        do {
            foreach ($alreadyYielded as $alreadyYield) {
                if ($alreadyYield === $throwable) {
                    break 2;
                }
            }

            yield $throwable;

            $alreadyYielded[] = $throwable;

            $throwable = $throwable->getPrevious();
        } while ($throwable !== null);
    }

    private function unpackAuditables(array $unpackables): TaggedStringCollection
    {
        $result = new TaggedStringCollection();

        foreach ($unpackables as $key => $unpackable) {
            foreach ($this->deepObjectIterator->iterate($unpackable) as $item) {
                if ($item instanceof AuditableDataAwareInterface) {
                    $result[$key]->getCollection()->push([
                        $this->auditableDataSerializer->serialize($item),
                    ]);
                }
            }
        }

        return $result;
    }
}
