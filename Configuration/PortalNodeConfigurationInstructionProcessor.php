<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\ClosureInstructionToken;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionLoaderInterface;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;
use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Psr\Log\LoggerInterface;

final class PortalNodeConfigurationInstructionProcessor implements PortalNodeConfigurationProcessorInterface
{
    private ?array $instructions = null;

    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private PortalRegistryInterface $portalRegistry;

    /**
     * @var InstructionLoaderInterface[]
     */
    private array $instructionLoaders;

    /**
     * @param iterable<InstructionLoaderInterface> $instructionLoaders
     */
    public function __construct(
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalRegistryInterface $portalRegistry,
        iterable $instructionLoaders
    ) {
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalRegistry = $portalRegistry;
        $this->instructionLoaders = \iterable_to_array($instructionLoaders);
    }

    public function read(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array
    {
        $instructions = $this->filterInstructions($portalNodeKey);
        $readConfig = static fn () => $read();

        foreach ($instructions as $instruction) {
            if ($instruction instanceof ClosureInstructionToken) {
                $instructionCall = $instruction->getClosure();
                $readConfigCall = $readConfig;
                $readConfig = static fn () => $instructionCall($readConfigCall);
            }
        }

        return $readConfig();
    }

    public function write(PortalNodeKeyInterface $portalNodeKey, array $payload, \Closure $write): void
    {
        $write($payload);
    }

    /**
     * @return InstructionTokenContract[]
     */
    private function filterInstructions(PortalNodeKeyInterface $portalNodeKey): array
    {
        $result = [];

        foreach ($this->getInstructions() as $instruction) {
            $query = $instruction->getQuery();

            if (\class_exists($query) || \interface_exists($query)) {
                if (
                    !$this->isQueryMatchingPortalNode($portalNodeKey, $query)
                    && !$this->isQueryMatchingPortalExtension($portalNodeKey, $query)
                ) {
                    continue;
                }
            } elseif (!$this->isQueryMatchingPortalNodeKey($portalNodeKey, $query)) {
                continue;
            }

            $result[] = $instruction;
        }

        return $result;
    }

    private function isQueryMatchingPortalNodeKey(PortalNodeKeyInterface $portalNodeKey, string $query): bool
    {
        try {
            return $this->storageKeyGenerator->deserialize($query)->equals($portalNodeKey);
        } catch (UnsupportedStorageKeyException $e) {
            if ($this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias()) === $query) {
                return true;
            }

            if ($this->storageKeyGenerator->serialize($portalNodeKey->withAlias()) === $query) {
                return true;
            }

            return false;
        }
    }

    /**
     * @param class-string $classString
     */
    private function isQueryMatchingPortalNode(PortalNodeKeyInterface $portalNodeKey, string $classString): bool
    {
        $portal = $this->portalRegistry->getPortal($portalNodeKey);

        return $portal instanceof $classString;
    }

    /**
     * @param class-string $classString
     */
    private function isQueryMatchingPortalExtension(PortalNodeKeyInterface $portalNodeKey, string $classString): bool
    {
        foreach ($this->portalRegistry->getPortalExtensions($portalNodeKey) as $portalExtension) {
            if ($portalExtension instanceof $classString) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return InstructionTokenContract[]
     */
    private function getInstructions(): array
    {
        return $this->instructions ??= $this->loadInstructions();
    }

    /**
     * @return InstructionTokenContract[]
     */
    private function loadInstructions(): array
    {
        $result = [];

        foreach ($this->instructionLoaders as $instructionLoader) {
            try {
                $result[] = $instructionLoader->loadInstructions();
            } catch (\Throwable $throwable) {
                $this->logger->critical('Failed loading instructions', [
                    'class' => \get_class($instructionLoader),
                    'exception' => $throwable,
                    'code' => 1647826121,
                ]);
            }
        }

        return \array_merge([], ...$result);
    }
}
