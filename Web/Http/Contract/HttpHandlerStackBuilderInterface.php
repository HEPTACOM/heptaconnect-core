<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;

/**
 * Describes a stack builder, that is used to order @see HttpHandlerContract to create a @see HttpHandlerStackInterface
 */
interface HttpHandlerStackBuilderInterface
{
    /**
     * Add a handler to the stack, that is not defined in the stack building scenario.
     */
    public function push(HttpHandlerContract $httpHandler): self;

    /**
     * Add the handler, that is seen as the source of the first set of data in the stack building scenario.
     */
    public function pushSource(): self;

    /**
     * Add the handlers, that are seen as modifiers of the first set of data in the stack building scenario.
     */
    public function pushDecorators(): self;

    /**
     * Creates a stack, that previously has been defined by @see push, pushSource, pushDecorators
     */
    public function build(): HttpHandlerStackInterface;

    /**
     * Returns true, when no handlers are registered for stack building.
     */
    public function isEmpty(): bool;
}
