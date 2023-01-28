<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

class UnexpectedClassInheritanceOnInstantionException extends AbstractInstantiationException
{
    /**
     * @psalm-var class-string
     */
    private string $expectedInheritedClass;

    /**
     * @psalm-param class-string $class
     * @psalm-param class-string $expectedInheritedClass
     */
    public function __construct(string $class, string $expectedInheritedClass, ?\Throwable $previous = null)
    {
        parent::__construct($class, $previous);

        $this->expectedInheritedClass = $expectedInheritedClass;
    }

    /**
     * @psalm-return class-string
     */
    public function getExpectedInheritedClass(): string
    {
        return $this->expectedInheritedClass;
    }
}
