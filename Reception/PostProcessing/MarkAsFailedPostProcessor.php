<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Reception\Contract\PostProcessorContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityError\IdentityErrorCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;

final class MarkAsFailedPostProcessor extends PostProcessorContract
{
    public function __construct(
        private IdentityErrorCreateActionInterface $identityErrorCreateAction
    ) {
    }

    public function handle(PostReceptionEvent $event): void
    {
        $markAsFailedData = \iterable_map(
            $event->getContext()->getPostProcessingBag()->of(MarkAsFailedData::class),
            static fn (MarkAsFailedData $data) => $data
        );

        /** @var MarkAsFailedData $data */
        foreach ($markAsFailedData as $data) {
            $mapping = $data->getEntity()->getAttachment(PrimaryKeySharingMappingStruct::class);

            if ($mapping instanceof MappingInterface) {
                $externalId = $mapping->getExternalId();

                if ($externalId !== null) {
                    $mappingComponent = new MappingComponentStruct(
                        $mapping->getPortalNodeKey(),
                        $mapping->getEntityType(),
                        $externalId
                    );
                    $payload = new IdentityErrorCreatePayload($mappingComponent, $data->getThrowable());

                    $this->identityErrorCreateAction->create(new IdentityErrorCreatePayloads([$payload]));

                    continue;
                }
            }

            $event->getContext()->getLogger()->error(LogMessage::MARK_AS_FAILED_ENTITY_IS_UNMAPPED(), [
                'throwable' => $data->getThrowable(),
                'data' => $data,
                'code' => 1637456198,
            ]);
        }
    }
}
