<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

/**
 * Describes a stack builder, that is used to order @see ReceiverContract to create a @see ReceiverStackInterface
 */
interface ReceiverStackBuilderInterface
{
    /**
     * Add a receiver to the stack, that is not defined in the stack building scenario.
     */
    public function push(ReceiverContract $receiver): self;

    /**
     * Add the receiver, that is seen as the first processor for the set of data in the stack building scenario.
     */
    public function pushSource(): self;

    /**
     * Add the receiver, that are seen as the follow-up processors for the set of data in the stack building scenario.
     */
    public function pushDecorators(): self;

    /**
     * Creates a stack, that previously has been defined by @see push, pushSource, pushDecorators
     */
    public function build(): ReceiverStackInterface;

    /**
     * Returns true, when no receivers are registered for stack building.
     */
    public function isEmpty(): bool;
}
