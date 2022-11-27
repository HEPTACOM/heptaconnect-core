<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\ClosureInstructionToken;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionLoaderInterface;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;
use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final class PortalNodeConfigurationInstructionProcessor implements PortalNodeConfigurationProcessorInterface
{
    private ?array $instructions = null;

    /**
     * @var InstructionLoaderInterface[]
     */
    private array $instructionLoaders;

    /**
     * @param iterable<InstructionLoaderInterface> $instructionLoaders
     */
    public function __construct(
        private LoggerInterface $logger,
        private PortalRegistryInterface $portalRegistry,
        private PackageQueryMatcherInterface $packageQueryMatcher,
        iterable $instructionLoaders
    ) {
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
        $portalExtensions = null;

        foreach ($this->getInstructions() as $instruction) {
            $query = $instruction->getQuery();
            $matchedKeys = $this->packageQueryMatcher->matchPortalNodeKeys($query, new PortalNodeKeyCollection([
                $portalNodeKey,
            ]));

            if ($matchedKeys->count() > 0) {
                $result[] = $instruction;

                continue;
            }

            $portalExtensions ??= $this->portalRegistry->getPortalExtensions($portalNodeKey);
            $matchedPortals = $this->packageQueryMatcher->matchPortals($query, new PortalCollection([
                $this->portalRegistry->getPortal($portalNodeKey),
            ]));

            if ($matchedPortals->count() > 0) {
                $result[] = $instruction;

                continue;
            }

            $matchedPortalExtensions = $this->packageQueryMatcher->matchPortalExtensions($query, $portalExtensions);

            if ($matchedPortalExtensions->count() > 0) {
                $result[] = $instruction;
            }
        }

        return $result;
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
                    'class' => $instructionLoader::class,
                    'exception' => $throwable,
                    'code' => 1647826121,
                ]);
            }
        }

        return \array_merge([], ...$result);
    }
}
