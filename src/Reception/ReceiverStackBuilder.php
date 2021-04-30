<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ReceiverStackBuilder implements ReceiverStackBuilderInterface
{
    private PortalRegistryInterface $portalRegistry;

    private LoggerInterface $logger;

    private PortalNodeKeyInterface $portalNodeKey;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityClassName;

    /**
     * @var ReceiverContract[]
     */
    private array $receivers = [];

    private ?PortalContract $cachedPortal = null;

    private ?PortalExtensionCollection $cachedPortalExtensions = null;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityClassName
     */
    public function __construct(
        PortalRegistryInterface $portalRegistry,
        LoggerInterface $logger,
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalNodeKey = $portalNodeKey;
        $this->entityClassName = $entityClassName;
        $this->logger = $logger;
    }

    public function push(ReceiverContract $receiver): self
    {
        if (\is_a($this->entityClassName, $receiver->supports(), true)) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as arbitrary receiver.',
                \get_class($receiver)
            ));

            $this->receivers[] = $receiver;
        } else {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Tried to push %s as arbitrary receiver, but it does not support type %s.',
                \get_class($receiver),
                $this->entityClassName,
            ));
        }

        return $this;
    }

    public function pushSource(): self
    {
        $lastReceiver = null;

        foreach ($this->getPortal()->getReceivers()->bySupport($this->entityClassName) as $receiver) {
            $lastReceiver = $receiver;
        }

        if ($lastReceiver instanceof ReceiverContract) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as source receiver.',
                \get_class($lastReceiver)
            ));

            $this->receivers[] = $lastReceiver;
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->getPortalExtensions()->getReceiverDecorators()->bySupport($this->entityClassName) as $receiver) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as decorator receiver.',
                \get_class($receiver)
            ));

            $this->receivers[] = $receiver;
        }

        return $this;
    }

    public function build(): ReceiverStackInterface
    {
        $receiverStack = new ReceiverStack(\array_map(
            static fn (ReceiverContract $e) => clone $e,
            \array_reverse($this->receivers, false),
        ));

        if ($receiverStack instanceof LoggerAwareInterface) {
            $receiverStack->setLogger($this->logger);
        }

        $this->logger->debug('ReceiverStackBuilder: Built receiver stack.');

        return $receiverStack;
    }

    public function isEmpty(): bool
    {
        return empty($this->receivers);
    }

    private function getPortal(): PortalContract
    {
        return $this->cachedPortal ??= $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    private function getPortalExtensions(): PortalExtensionCollection
    {
        return $this->cachedPortalExtensions ??= $this->portalRegistry->getPortalExtensions($this->portalNodeKey);
    }
}
