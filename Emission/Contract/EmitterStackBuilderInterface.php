<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

/**
 * Describes a stack builder, that is used to order @see EmitterContract to create a @see EmitterStackInterface
 */
interface EmitterStackBuilderInterface
{
    /**
     * Add an emitter to the stack, that is not defined in the stack building scenario.
     */
    public function push(EmitterContract $emitter): self;

    /**
     * Add the emitter, that is seen as the source of the first set of data in the stack building scenario.
     */
    public function pushSource(): self;

    /**
     * Add the emitters, that are seen as modifiers of the first set of data in the stack building scenario.
     */
    public function pushDecorators(): self;

    /**
     * Creates a stack, that previously has been defined by @see push, pushSource, pushDecorators
     */
    public function build(): EmitterStackInterface;

    /**
     * Returns true, when no emitters are registered for stack building.
     */
    public function isEmpty(): bool;
}
