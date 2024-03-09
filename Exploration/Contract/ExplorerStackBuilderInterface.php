<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

/**
 * Describes a stack builder, that is used to order @see ExplorerContract to create a @see ExplorerStackInterface
 */
interface ExplorerStackBuilderInterface
{
    /**
     * Add an explorer to the stack, that is not defined in the stack building scenario.
     */
    public function push(ExplorerContract $explorer): self;

    /**
     * Add the explorer, that is seen as the source of the first set of data in the stack building scenario.
     */
    public function pushSource(): self;

    /**
     * Add the explorers, that are seen as modifiers of the first set of data in the stack building scenario.
     */
    public function pushDecorators(): self;

    /**
     * Creates a stack, that previously has been defined by @see push, pushSource, pushDecorators
     */
    public function build(): ExplorerStackInterface;

    /**
     * Returns true, when no explorers are registered for stack building.
     */
    public function isEmpty(): bool;
}
